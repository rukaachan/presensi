<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** @var array<string, true> */
    private array $migratedEvidencePaths = [];

    public function up(): void
    {
        if (! Schema::hasTable('akun')) {
            return;
        }

        $this->ensureSessionCatalog();
        $this->renameCanonicalSourceColumns();
        $this->assertLegacyRowsAreResolvable();
        $this->backfillAttendanceRecords();
        $this->backfillAttendanceEvents();
        $this->backfillLeaveRequests();
        $this->backfillAuditEvents();
        $this->verifyParityBeforeDrop();
        $this->dropLegacySessionColumn();

        Schema::disableForeignKeyConstraints();
        $this->dropLegacyViews();
        $this->dropLegacySourceTables();
        $this->renameBaseTables();
        $this->renameBaseColumns();
        $this->normalizeCanonicalValues();
        $this->ensureCanonicalRoleCodes();
        $this->renameCanonicalSourceColumns();
        $this->rebuildCanonicalForeignKeys();
        $this->rebuildCanonicalIndexes();
        $this->createCanonicalViews();
        Schema::enableForeignKeyConstraints();
        $this->removeMigratedEvidence();
    }

    public function down(): void
    {
        throw new RuntimeException('The English contract migration is irreversible. Restore a pre-migration backup.');
    }

    private function ensureSessionCatalog(): void
    {
        if (! Schema::hasTable('attendance_sessions')) {
            throw new RuntimeException('The attendance session table is missing.');
        }

        foreach (config('attendance.sessions', []) as $session) {
            DB::table('attendance_sessions')->updateOrInsert(
                ['code' => $session['code']],
                [
                    'label' => $session['label'],
                    'kind' => $session['kind'],
                    'required' => $session['required'],
                    'active' => $session['active'],
                    'window_start' => $session['window_start'],
                    'window_end' => $session['window_end'],
                    'sort_order' => $session['sort_order'],
                    'settings' => json_encode($session['settings'], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function backfillAttendanceRecords(): void
    {
        if (! Schema::hasTable('presensi_siswa') || ! Schema::hasTable('attendance_records')) {
            return;
        }

        $sessionId = DB::table('attendance_sessions')->where('code', 'daily_check_in')->value('id');
        if ($sessionId === null) {
            throw new RuntimeException('The required daily attendance session is missing.');
        }

        foreach (DB::table('presensi_siswa')->orderBy('id_presensi')->get() as $legacy) {
            $existing = DB::table('attendance_records')
                ->where('source_record_id', $legacy->id_presensi)
                ->first();
            $state = $this->attendanceState($legacy->status_kehadiran);
            $capturedAt = $this->parseTimestamp($legacy->tanggal, $legacy->jam_masuk);
            $evidence = $this->moveEvidence($legacy->foto_bukti, (int) $legacy->id_presensi);
            $values = [
                'student_id' => $legacy->id_siswa,
                'attendance_session_id' => $sessionId,
                'attendance_date' => $legacy->tanggal,
                'state' => $state,
                'late' => false,
                'captured_at' => $capturedAt,
                'evidence_disk' => $evidence['disk'],
                'evidence_path' => $evidence['path'],
                'evidence_hash' => $evidence['hash'],
                'evidence_mime' => $evidence['mime'],
                'evidence_bytes' => $evidence['bytes'],
                'notes' => $legacy->keterangan,
                'source' => 'migration',
                'idempotency_key' => 'migration:attendance:'.$legacy->id_presensi,
                'source_record_id' => $legacy->id_presensi,
                'updated_at' => now(),
            ];

            if ($existing === null) {
                DB::table('attendance_records')->insert($values + ['created_at' => now()]);
            } elseif ($existing->source === 'legacy' || $existing->source === 'migration') {
                DB::table('attendance_records')
                    ->where('id', $existing->id)
                    ->update($values);
            }
        }
    }

    private function backfillAttendanceEvents(): void
    {
        if (! Schema::hasTable('validasi') || ! Schema::hasTable('attendance_events')) {
            return;
        }

        foreach (DB::table('validasi')->orderBy('id_validasi')->get() as $legacy) {
            $attendance = DB::table('presensi_siswa')->where('id_presensi', $legacy->id_presensi)->first();
            $sessionId = DB::table('attendance_sessions')
                ->where('code', $this->sessionCode($legacy->waktu_validasi))
                ->value('id');
            if ($attendance === null || $sessionId === null) {
                continue;
            }

            $studentId = (int) $attendance->id_siswa;
            $observedBy = DB::table('pengurus_kelas')
                ->join('siswa', 'siswa.id_siswa', '=', 'pengurus_kelas.id_siswa')
                ->where('pengurus_kelas.id_pengurus', $legacy->id_pengurus)
                ->value('siswa.id_akun');
            $values = [
                'student_id' => $studentId,
                'attendance_session_id' => $sessionId,
                'event_date' => $attendance->tanggal,
                'state' => $this->validationState($legacy->status_validasi),
                'proposed_status' => $this->proposedStatus($legacy->status_validasi),
                'observed_at' => null,
                'notes' => null,
                'source' => 'migration',
                'observed_by' => $observedBy,
                'idempotency_key' => 'migration:event:'.$legacy->id_validasi,
                'source_event_id' => $legacy->id_validasi,
                'source_attendance_id' => $legacy->id_presensi,
                'updated_at' => now(),
            ];
            $existing = DB::table('attendance_events')
                ->where('source_event_id', $legacy->id_validasi)
                ->first();

            if ($existing === null) {
                DB::table('attendance_events')->insert($values + ['created_at' => now()]);
            } elseif ($existing->source === 'legacy' || $existing->source === 'migration') {
                DB::table('attendance_events')
                    ->where('id', $existing->id)
                    ->update($values);
            }
        }
    }

    private function backfillLeaveRequests(): void
    {
        if (! Schema::hasTable('surat_keterangan') || ! Schema::hasTable('leave_requests')) {
            return;
        }

        foreach (DB::table('surat_keterangan')->orderBy('id_presensi')->get() as $legacy) {
            $attendance = DB::table('presensi_siswa')->where('id_presensi', $legacy->id_presensi)->first();
            if ($attendance === null) {
                continue;
            }

            $recordId = DB::table('attendance_records')
                ->where('source_record_id', $legacy->id_presensi)
                ->value('id');
            $values = [
                'student_id' => $attendance->id_siswa,
                'attendance_record_id' => $recordId,
                'state' => 'submitted',
                'reason' => $legacy->surat_keterangan ?: 'Alasan tidak dicatat pada data lama.',
                'attachment_disk' => null,
                'attachment_path' => null,
                'submitted_by' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'decision_note' => null,
                'source_letter_id' => $legacy->id_presensi,
                'updated_at' => now(),
            ];
            $existing = DB::table('leave_requests')
                ->where('source_letter_id', $legacy->id_presensi)
                ->first();
            if ($existing === null) {
                DB::table('leave_requests')->insert($values + ['created_at' => now()]);
            }
        }
    }

    private function backfillAuditEvents(): void
    {
        if (! Schema::hasTable('logs') || ! Schema::hasTable('audit_events')) {
            return;
        }

        foreach (DB::table('logs')->orderBy('id_log')->get() as $legacy) {
            $occurredAt = $this->parseTimestamp($legacy->tanggal, $legacy->jam) ?: now();
            $values = [
                'actor_id' => null,
                'actor_type' => 'migration',
                'source_actor' => $legacy->aktor,
                'action' => $this->auditAction($legacy->aksi),
                'subject_type' => $this->subjectType($legacy->tabel),
                'subject_id' => null,
                'before' => null,
                'after' => null,
                'metadata' => json_encode([
                    'raw_record' => $legacy->record,
                    'legacy_status' => $legacy->status,
                ], JSON_THROW_ON_ERROR),
                'occurred_at' => $occurredAt,
                'source_log_id' => $legacy->id_log,
                'updated_at' => now(),
            ];
            $existing = DB::table('audit_events')
                ->where('source_log_id', $legacy->id_log)
                ->first();

            if ($existing === null) {
                DB::table('audit_events')->insert($values + ['created_at' => now()]);
            } elseif ($existing->actor_type === 'legacy' || $existing->actor_type === 'migration' || $existing->source_actor !== null) {
                DB::table('audit_events')->where('id', $existing->id)->update($values);
            }
        }
    }

    private function assertLegacyRowsAreResolvable(): void
    {
        $issues = [];

        foreach ([
            'guru' => 'id_akun',
            'tata_usaha' => 'id_akun',
            'siswa' => 'id_akun',
            'guru_piket' => 'id_guru',
            'guru_bk' => 'id_guru',
            'pengurus_kelas' => 'id_siswa',
        ] as $table => $column) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $duplicates = DB::table($table)
                ->select($column, DB::raw('COUNT(*) AS aggregate'))
                ->whereNotNull($column)
                ->groupBy($column)
                ->havingRaw('COUNT(*) > 1')
                ->limit(10)
                ->get();

            foreach ($duplicates as $duplicate) {
                $issues[] = $table.'.'.$column.'='.$duplicate->{$column};
            }
        }

        foreach ([
            ['table' => 'presensi_siswa', 'columns' => ['id_siswa', 'tanggal']],
            ['table' => 'validasi', 'columns' => ['id_presensi', 'waktu_validasi']],
            ['table' => 'surat_keterangan', 'columns' => ['id_presensi']],
        ] as $check) {
            if (! Schema::hasTable($check['table'])) {
                continue;
            }

            $duplicates = DB::table($check['table'])
                ->select(array_merge($check['columns'], [DB::raw('COUNT(*) AS aggregate')]))
                ->groupBy($check['columns'])
                ->havingRaw('COUNT(*) > 1')
                ->limit(10)
                ->get();

            foreach ($duplicates as $duplicate) {
                $key = collect($check['columns'])
                    ->map(static fn (string $column): string => $column.'='.(string) ($duplicate->{$column} ?? 'NULL'))
                    ->implode(',');
                $issues[] = $check['table'].'.'.$key;
            }
        }

        if (Schema::hasTable('presensi_siswa')) {
            foreach (DB::table('presensi_siswa')->select(['id_presensi', 'id_siswa'])->get() as $legacy) {
                if (! DB::table('siswa')->where('id_siswa', $legacy->id_siswa)->exists()) {
                    $issues[] = 'presensi_siswa.id_presensi='.$legacy->id_presensi.' has no student';
                }
            }
        }

        if (Schema::hasTable('validasi')) {
            foreach (DB::table('validasi')->select(['id_validasi', 'id_presensi', 'waktu_validasi'])->get() as $legacy) {
                $attendance = DB::table('presensi_siswa')
                    ->where('id_presensi', $legacy->id_presensi)
                    ->first();
                if ($attendance === null) {
                    $issues[] = 'validasi.id_validasi='.$legacy->id_validasi.' has no attendance record';

                    continue;
                }
                if (! DB::table('attendance_sessions')->where('code', $this->sessionCode($legacy->waktu_validasi))->exists()) {
                    $issues[] = 'validasi.id_validasi='.$legacy->id_validasi.' has unsupported session '.$legacy->waktu_validasi;
                }
            }
        }

        if (Schema::hasTable('surat_keterangan')) {
            foreach (DB::table('surat_keterangan')->select(['id_presensi'])->get() as $legacy) {
                if ($legacy->id_presensi === null || ! DB::table('presensi_siswa')->where('id_presensi', $legacy->id_presensi)->exists()) {
                    $issues[] = 'surat_keterangan.id_presensi='.(string) ($legacy->id_presensi ?? 'NULL').' has no attendance record';
                }
            }
        }

        if ($issues !== []) {
            $preview = implode('; ', array_slice($issues, 0, 20));
            $suffix = count($issues) > 20 ? ' (additional issues omitted)' : '';
            throw new RuntimeException('English contract migration cannot resolve legacy rows: '.$preview.$suffix);
        }
    }

    private function verifyParityBeforeDrop(): void
    {
        $mappings = [
            ['presensi_siswa', 'id_presensi', 'attendance_records', 'source_record_id'],
            ['validasi', 'id_validasi', 'attendance_events', 'source_event_id'],
            ['surat_keterangan', 'id_presensi', 'leave_requests', 'source_letter_id'],
            ['logs', 'id_log', 'audit_events', 'source_log_id'],
        ];
        $mismatches = [];

        foreach ($mappings as [$sourceTable, $sourceId, $targetTable, $targetId]) {
            if (! Schema::hasTable($sourceTable) || ! Schema::hasTable($targetTable)) {
                continue;
            }

            $sourceCount = DB::table($sourceTable)->count();
            $targetCount = DB::table($targetTable)->whereNotNull($targetId)->count();
            if ($sourceCount !== $targetCount) {
                $mismatches[] = $sourceTable.' rows '.$sourceCount.' != '.$targetTable.' provenance rows '.$targetCount;
            }
        }

        if (Schema::hasTable('presensi_siswa') && Schema::hasTable('attendance_records')) {
            $actual = DB::table('presensi_siswa')
                ->join('attendance_records', 'attendance_records.source_record_id', '=', 'presensi_siswa.id_presensi')
                ->select('presensi_siswa.status_kehadiran AS legacy_state', 'attendance_records.state', DB::raw('COUNT(*) AS aggregate'))
                ->groupBy('presensi_siswa.status_kehadiran', 'attendance_records.state')
                ->get();
            foreach ($actual as $row) {
                if ($row->state !== $this->attendanceState($row->legacy_state)) {
                    $mismatches[] = 'attendance state '.$row->legacy_state.' mapped to unexpected '.$row->state;
                }
            }
        }

        if (Schema::hasTable('validasi') && Schema::hasTable('attendance_events')) {
            $actual = DB::table('validasi')
                ->join('attendance_events', 'attendance_events.source_event_id', '=', 'validasi.id_validasi')
                ->select('validasi.status_validasi AS legacy_state', 'attendance_events.state', DB::raw('COUNT(*) AS aggregate'))
                ->groupBy('validasi.status_validasi', 'attendance_events.state')
                ->get();
            foreach ($actual as $row) {
                if ($row->state !== $this->validationState($row->legacy_state)) {
                    $mismatches[] = 'event state '.$row->legacy_state.' mapped to unexpected '.$row->state;
                }
            }
        }

        if ($mismatches !== []) {
            throw new RuntimeException('English contract migration parity check failed: '.implode('; ', $mismatches));
        }
    }

    private function dropLegacySessionColumn(): void
    {
        if (! Schema::hasColumn('attendance_sessions', 'legacy_code')) {
            return;
        }

        $this->dropIndexIfExists('attendance_sessions', 'attendance_sessions_legacy_code_unique');
        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->dropColumn('legacy_code');
        });
    }

    private function dropLegacyViews(): void
    {
        foreach (['view_presensi', 'view_siswa'] as $view) {
            DB::statement("DROP VIEW IF EXISTS {$view}");
        }
    }

    private function dropLegacySourceTables(): void
    {
        foreach (['validasi', 'surat_keterangan', 'logs', 'presensi_siswa'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function renameBaseTables(): void
    {
        $tables = [
            'role_akun' => 'roles',
            'akun' => 'accounts',
            'tata_usaha' => 'administration_staff',
            'guru' => 'teachers',
            'guru_piket' => 'duty_teachers',
            'guru_bk' => 'counseling_teachers',
            'jurusan' => 'departments',
            'kelas' => 'classrooms',
            'siswa' => 'students',
            'pengurus_kelas' => 'class_officers',
        ];

        foreach ($tables as $from => $to) {
            if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }
        }
    }

    private function renameBaseColumns(): void
    {
        $maps = [
            'roles' => ['id_role' => 'id', 'nama_role' => 'name'],
            'accounts' => ['id_akun' => 'id', 'id_role' => 'role_id'],
            'administration_staff' => ['id_tata_usaha' => 'id', 'id_akun' => 'account_id', 'nama_tata_usaha' => 'name', 'foto_tata_usaha' => 'photo_path'],
            'teachers' => ['id_guru' => 'id', 'id_akun' => 'account_id', 'nama_guru' => 'name', 'foto_guru' => 'photo_path', 'pembuat' => 'created_by_label'],
            'duty_teachers' => ['id_piket' => 'id', 'id_guru' => 'teacher_id'],
            'counseling_teachers' => ['id_bk' => 'id', 'id_guru' => 'teacher_id'],
            'departments' => ['id_jurusan' => 'id', 'nama_jurusan' => 'name', 'pembuat' => 'created_by_label'],
            'classrooms' => ['id_kelas' => 'id', 'id_wali_kelas' => 'homeroom_teacher_id', 'id_jurusan' => 'department_id', 'nama_kelas' => 'name', 'tingkatan' => 'grade_level', 'status_kelas' => 'status', 'pembuat' => 'created_by_label'],
            'students' => ['id_siswa' => 'id', 'id_akun' => 'account_id', 'id_kelas' => 'classroom_id', 'nis' => 'student_number', 'nama_siswa' => 'name', 'nomer_hp' => 'phone', 'jenis_kelamin' => 'gender', 'status_siswa' => 'status', 'status_jabatan' => 'position', 'angkatan' => 'admission_year', 'foto_siswa' => 'photo_path', 'pembuat' => 'created_by_label'],
            'class_officers' => ['id_pengurus' => 'id', 'id_siswa' => 'student_id', 'jabatan' => 'position', 'pembuat' => 'created_by_label'],
        ];

        foreach ($maps as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                foreach ($columns as $from => $to) {
                    if (Schema::hasColumn($blueprint->getTable(), $from) && ! Schema::hasColumn($blueprint->getTable(), $to)) {
                        $blueprint->renameColumn($from, $to);
                    }
                }
            });
        }
    }

    private function normalizeCanonicalValues(): void
    {
        if (! Schema::hasTable('class_officers')) {
            return;
        }

        foreach (['Pengurus Classroom', 'Pengurus Kelas', 'pengurus_kelas'] as $legacyPosition) {
            DB::table('class_officers')
                ->where('position', $legacyPosition)
                ->update(['position' => 'class_officer']);
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->rebuildSqliteValueTables();
        } else {
            DB::statement('ALTER TABLE classrooms MODIFY status VARCHAR(32) NOT NULL');
            DB::statement('ALTER TABLE students MODIFY gender VARCHAR(32) NOT NULL');
            DB::statement('ALTER TABLE students MODIFY status VARCHAR(32) NOT NULL');
            DB::statement('ALTER TABLE students MODIFY position VARCHAR(32) NULL');
        }

        DB::table('classrooms')->where('status', 'aktif')->update(['status' => 'active']);
        DB::table('classrooms')->where('status', 'tidak_aktif')->update(['status' => 'inactive']);
        DB::table('classrooms')->where('status', 'lulus')->update(['status' => 'graduated']);
        DB::table('students')->where('gender', 'laki-laki')->update(['gender' => 'male']);
        DB::table('students')->where('gender', 'perempuan')->update(['gender' => 'female']);
        DB::table('students')->where('status', 'aktif')->update(['status' => 'active']);
        DB::table('students')->where('status', 'tinggal_kelas')->update(['status' => 'retained']);
        DB::table('students')->where('status', 'lulus')->update(['status' => 'graduated']);
        DB::table('students')->where('position', 'sekretaris')->update(['position' => 'secretary']);
        DB::table('students')->where('position', 'ketua_kelas')->update(['position' => 'class_president']);
        DB::table('students')->where('position', 'wakil_kelas')->update(['position' => 'vice_president']);
        DB::table('students')->where('position', 'bendahara')->update(['position' => 'treasurer']);
        DB::table('students')->where('position', 'siswa')->update(['position' => 'student']);
        DB::table('students')->where('position', '')->update(['position' => null]);

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE classrooms MODIFY status ENUM('active', 'inactive', 'graduated') NOT NULL");
            DB::statement("ALTER TABLE students MODIFY gender ENUM('male', 'female') NOT NULL");
            DB::statement("ALTER TABLE students MODIFY status ENUM('active', 'retained', 'graduated') NOT NULL");
            DB::statement("ALTER TABLE students MODIFY position ENUM('secretary', 'class_president', 'vice_president', 'treasurer', 'student') NULL");
        }
    }

    private function rebuildSqliteValueTables(): void
    {
        DB::statement("CREATE TABLE classrooms_value_migration (id INTEGER PRIMARY KEY AUTOINCREMENT, homeroom_teacher_id INTEGER NULL, department_id INTEGER NOT NULL, name VARCHAR NOT NULL, grade_level VARCHAR NOT NULL, status VARCHAR NOT NULL CHECK (status IN ('active', 'inactive', 'graduated')), created_by_label VARCHAR NOT NULL, FOREIGN KEY (homeroom_teacher_id) REFERENCES teachers(id) ON DELETE CASCADE ON UPDATE CASCADE, FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE ON UPDATE CASCADE)");
        DB::statement("INSERT INTO classrooms_value_migration (id, homeroom_teacher_id, department_id, name, grade_level, status, created_by_label) SELECT id, homeroom_teacher_id, department_id, name, grade_level, CASE status WHEN 'aktif' THEN 'active' WHEN 'tidak_aktif' THEN 'inactive' WHEN 'lulus' THEN 'graduated' ELSE status END, created_by_label FROM classrooms");
        Schema::drop('classrooms');
        Schema::rename('classrooms_value_migration', 'classrooms');

        DB::statement("CREATE TABLE students_value_migration (id INTEGER PRIMARY KEY AUTOINCREMENT, account_id INTEGER NOT NULL, classroom_id INTEGER NOT NULL, student_number INTEGER NOT NULL, name VARCHAR NOT NULL, phone VARCHAR NOT NULL, gender VARCHAR NOT NULL CHECK (gender IN ('male', 'female')), status VARCHAR NOT NULL CHECK (status IN ('active', 'retained', 'graduated')), position VARCHAR NULL CHECK (position IS NULL OR position IN ('secretary', 'class_president', 'vice_president', 'treasurer', 'student')), admission_year INTEGER NOT NULL, photo_path TEXT NOT NULL, created_by_label VARCHAR NOT NULL, FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE ON UPDATE CASCADE, FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE ON UPDATE CASCADE)");
        DB::statement("INSERT INTO students_value_migration (id, account_id, classroom_id, student_number, name, phone, gender, status, position, admission_year, photo_path, created_by_label) SELECT id, account_id, classroom_id, student_number, name, phone, CASE gender WHEN 'laki-laki' THEN 'male' WHEN 'perempuan' THEN 'female' ELSE gender END, CASE status WHEN 'aktif' THEN 'active' WHEN 'tinggal_kelas' THEN 'retained' WHEN 'lulus' THEN 'graduated' ELSE status END, CASE position WHEN 'sekretaris' THEN 'secretary' WHEN 'ketua_kelas' THEN 'class_president' WHEN 'wakil_kelas' THEN 'vice_president' WHEN 'bendahara' THEN 'treasurer' WHEN 'siswa' THEN 'student' ELSE position END, admission_year, photo_path, created_by_label FROM students");
        Schema::drop('students');
        Schema::rename('students_value_migration', 'students');

    }

    private function ensureCanonicalRoleCodes(): void
    {
        if (! Schema::hasColumn('roles', 'code')) {
            Schema::table('roles', function (Blueprint $table): void {
                $table->string('code', 50)->nullable();
            });
        }

        foreach (DB::table('roles')->get(['id', 'name']) as $role) {
            DB::table('roles')->where('id', $role->id)->update([
                'code' => $this->roleCode((string) $role->name),
            ]);
        }

        $this->addIndexIfMissing('roles', ['code'], 'roles_code_unique', true);

        foreach (['name', 'photo_path'] as $column) {
            if (! Schema::hasColumn('administration_staff', $column)) {
                Schema::table('administration_staff', function (Blueprint $table) use ($column): void {
                    $table->string($column)->nullable();
                });
            }
        }
    }

    private function roleCode(string $name): string
    {
        return match (Str::slug($name, '_')) {
            'siswa', 'student' => 'student',
            'wali_kelas', 'homeroom_teacher' => 'homeroom_teacher',
            'pengurus_kelas', 'class_officer' => 'class_officer',
            'guru_piket', 'duty_teacher' => 'duty_teacher',
            'guru_bk', 'counseling_teacher' => 'counseling_teacher',
            'tata_usaha', 'administrator' => 'administrator',
            default => Str::slug($name, '_'),
        };
    }

    private function renameCanonicalSourceColumns(): void
    {
        $maps = [
            'attendance_records' => ['legacy_presensi_id' => 'source_record_id'],
            'attendance_events' => ['legacy_validasi_id' => 'source_event_id', 'legacy_presensi_id' => 'source_attendance_id'],
            'leave_requests' => ['legacy_surat_presensi_id' => 'source_letter_id'],
            'audit_events' => ['legacy_actor' => 'source_actor', 'legacy_log_id' => 'source_log_id'],
        ];

        foreach ($maps as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns): void {
                foreach ($columns as $from => $to) {
                    if (Schema::hasColumn($table, $from) && ! Schema::hasColumn($table, $to)) {
                        $blueprint->renameColumn($from, $to);
                    }
                }
            });
        }
    }

    private function rebuildCanonicalForeignKeys(): void
    {
        $definitions = [
            'accounts' => [
                ['column' => 'role_id', 'table' => 'roles', 'references' => 'id', 'delete' => 'cascade', 'update' => 'cascade'],
            ],
            'administration_staff' => [
                ['column' => 'account_id', 'table' => 'accounts', 'references' => 'id', 'delete' => 'cascade', 'update' => 'cascade'],
            ],
            'teachers' => [
                ['column' => 'account_id', 'table' => 'accounts', 'references' => 'id', 'delete' => 'cascade', 'update' => 'cascade'],
            ],
            'duty_teachers' => [
                ['column' => 'teacher_id', 'table' => 'teachers', 'references' => 'id', 'delete' => 'cascade', 'update' => 'cascade'],
            ],
            'counseling_teachers' => [
                ['column' => 'teacher_id', 'table' => 'teachers', 'references' => 'id', 'delete' => 'cascade', 'update' => 'cascade'],
            ],
            'classrooms' => [
                ['column' => 'homeroom_teacher_id', 'table' => 'teachers', 'references' => 'id', 'delete' => 'cascade', 'update' => 'cascade'],
                ['column' => 'department_id', 'table' => 'departments', 'references' => 'id', 'delete' => 'cascade', 'update' => 'cascade'],
            ],
            'students' => [
                ['column' => 'account_id', 'table' => 'accounts', 'references' => 'id', 'delete' => 'cascade', 'update' => 'cascade'],
                ['column' => 'classroom_id', 'table' => 'classrooms', 'references' => 'id', 'delete' => 'cascade', 'update' => 'cascade'],
            ],
            'class_officers' => [
                ['column' => 'student_id', 'table' => 'students', 'references' => 'id', 'delete' => 'cascade', 'update' => 'cascade'],
            ],
            'attendance_records' => [
                ['column' => 'student_id', 'table' => 'students', 'references' => 'id', 'delete' => 'restrict', 'update' => 'restrict'],
                ['column' => 'attendance_session_id', 'table' => 'attendance_sessions', 'references' => 'id', 'delete' => 'restrict', 'update' => 'restrict'],
                ['column' => 'created_by', 'table' => 'accounts', 'references' => 'id', 'delete' => 'set null', 'update' => 'restrict'],
                ['column' => 'updated_by', 'table' => 'accounts', 'references' => 'id', 'delete' => 'set null', 'update' => 'restrict'],
            ],
            'attendance_events' => [
                ['column' => 'student_id', 'table' => 'students', 'references' => 'id', 'delete' => 'restrict', 'update' => 'restrict'],
                ['column' => 'attendance_session_id', 'table' => 'attendance_sessions', 'references' => 'id', 'delete' => 'restrict', 'update' => 'restrict'],
                ['column' => 'observed_by', 'table' => 'accounts', 'references' => 'id', 'delete' => 'set null', 'update' => 'restrict'],
                ['column' => 'reviewed_by', 'table' => 'accounts', 'references' => 'id', 'delete' => 'set null', 'update' => 'restrict'],
            ],
            'leave_requests' => [
                ['column' => 'student_id', 'table' => 'students', 'references' => 'id', 'delete' => 'restrict', 'update' => 'restrict'],
                ['column' => 'attendance_record_id', 'table' => 'attendance_records', 'references' => 'id', 'delete' => 'set null', 'update' => 'restrict'],
                ['column' => 'submitted_by', 'table' => 'accounts', 'references' => 'id', 'delete' => 'set null', 'update' => 'restrict'],
                ['column' => 'reviewed_by', 'table' => 'accounts', 'references' => 'id', 'delete' => 'set null', 'update' => 'restrict'],
            ],
            'audit_events' => [
                ['column' => 'actor_id', 'table' => 'accounts', 'references' => 'id', 'delete' => 'set null', 'update' => 'restrict'],
            ],
        ];

        foreach ($definitions as $table => $foreignKeys) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existing = Schema::getForeignKeys($table);
            if ($existing !== []) {
                Schema::table($table, function (Blueprint $blueprint) use ($existing): void {
                    foreach ($existing as $foreignKey) {
                        $blueprint->dropForeign($foreignKey['name'] ?? $foreignKey['columns']);
                    }
                });
            }

            Schema::table($table, function (Blueprint $blueprint) use ($foreignKeys): void {
                foreach ($foreignKeys as $foreignKey) {
                    $definition = $blueprint->foreign($foreignKey['column'])
                        ->references($foreignKey['references'])
                        ->on($foreignKey['table']);

                    if ($foreignKey['delete'] === 'cascade') {
                        $definition->cascadeOnDelete();
                    } elseif ($foreignKey['delete'] === 'set null') {
                        $definition->nullOnDelete();
                    } else {
                        $definition->restrictOnDelete();
                    }

                    if ($foreignKey['update'] === 'cascade') {
                        $definition->cascadeOnUpdate();
                    } else {
                        $definition->restrictOnUpdate();
                    }
                }
            });
        }
    }

    private function rebuildCanonicalIndexes(): void
    {
        $legacyIndexes = [
            'roles' => ['role_akun_id_role_index', 'role_akun_nama_role_unique'],
            'accounts' => ['akun_id_akun_index', 'akun_id_role_index', 'akun_username_unique'],
            'administration_staff' => ['tata_usaha_id_akun_unique'],
            'teachers' => ['guru_id_akun_unique'],
            'duty_teachers' => ['guru_piket_id_guru_unique'],
            'counseling_teachers' => ['guru_bk_id_guru_unique'],
            'students' => [
                'siswa_id_akun_index',
                'siswa_id_siswa_index',
                'siswa_nama_siswa_index',
                'siswa_id_akun_unique',
                'siswa_nis_unique',
            ],
            'class_officers' => ['pengurus_kelas_id_siswa_unique'],
            'attendance_records' => [
                'attendance_records_legacy_presensi_id_unique',
                'attendance_records_student_id_attendance_date_index',
            ],
            'attendance_events' => [
                'attendance_events_legacy_validasi_id_unique',
                'attendance_events_legacy_presensi_id_event_date_index',
            ],
            'leave_requests' => ['leave_requests_legacy_surat_presensi_id_unique'],
            'audit_events' => ['audit_events_legacy_log_id_unique'],
        ];

        foreach ($legacyIndexes as $table => $indexes) {
            foreach ($indexes as $index) {
                $this->dropIndexIfExists($table, $index);
            }
        }

        $indexes = [
            ['roles', ['name'], 'roles_name_unique', true],
            ['roles', ['code'], 'roles_code_unique', true],
            ['accounts', ['username'], 'accounts_username_unique', true],
            ['accounts', ['role_id'], 'accounts_role_id_index', false],
            ['administration_staff', ['account_id'], 'administration_staff_account_id_unique', true],
            ['teachers', ['account_id'], 'teachers_account_id_unique', true],
            ['duty_teachers', ['teacher_id'], 'duty_teachers_teacher_id_unique', true],
            ['counseling_teachers', ['teacher_id'], 'counseling_teachers_teacher_id_unique', true],
            ['classrooms', ['homeroom_teacher_id'], 'classrooms_homeroom_teacher_id_index', false],
            ['classrooms', ['department_id'], 'classrooms_department_id_index', false],
            ['students', ['account_id'], 'students_account_id_unique', true],
            ['students', ['student_number'], 'students_student_number_unique', true],
            ['students', ['account_id', 'classroom_id'], 'students_account_classroom_index', false],
            ['students', ['name'], 'students_name_index', false],
            ['class_officers', ['student_id'], 'class_officers_student_id_unique', true],
            ['attendance_records', ['student_id', 'attendance_date'], 'attendance_records_student_date_index', false],
            ['attendance_events', ['student_id', 'event_date'], 'attendance_events_student_date_index', false],
            ['attendance_events', ['source_attendance_id', 'event_date'], 'attendance_events_source_attendance_id_event_date_index', false],
        ];

        foreach ($indexes as [$table, $columns, $name, $unique]) {
            $this->addIndexIfMissing($table, $columns, $name, $unique);
        }
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropIndex($index);
        });
    }

    /** @param list<string> $columns */
    private function addIndexIfMissing(string $table, array $columns, string $index, bool $unique = false): void
    {
        if (! Schema::hasTable($table) || Schema::hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index, $unique): void {
            if ($unique) {
                $blueprint->unique($columns, $index);
            } else {
                $blueprint->index($columns, $index);
            }
        });
    }

    private function createCanonicalViews(): void
    {
        DB::statement('DROP VIEW IF EXISTS view_attendance');
        DB::statement('DROP VIEW IF EXISTS view_students');
        DB::statement('CREATE VIEW view_attendance AS SELECT attendance_records.*, students.name AS student_name, classrooms.name AS classroom_name, departments.name AS department_name FROM attendance_records JOIN students ON students.id = attendance_records.student_id JOIN classrooms ON classrooms.id = students.classroom_id JOIN departments ON departments.id = classrooms.department_id');
        DB::statement('CREATE VIEW view_students AS SELECT students.*, classrooms.grade_level, classrooms.name AS classroom_name, departments.name AS department_name FROM students JOIN classrooms ON students.classroom_id = classrooms.id JOIN departments ON classrooms.department_id = departments.id');
    }

    private function sessionCode(?string $legacyCode): string
    {
        return match ($legacyCode) {
            'istirahat_pertama' => 'break_1',
            'istirahat_kedua' => 'break_2',
            'istirahat_ketiga' => 'break_3',
            default => (string) $legacyCode,
        };
    }

    private function attendanceState(?string $status): string
    {
        return match ($status) {
            'hadir' => 'confirmed',
            'izin' => 'needs_review',
            'alpha' => 'absent',
            default => 'needs_review',
        };
    }

    private function validationState(?string $status): string
    {
        return match ($status) {
            'hadir' => 'confirmed',
            'izin' => 'needs_review',
            'alpha' => 'absent',
            'pulang' => 'confirmed',
            default => 'needs_review',
        };
    }

    private function proposedStatus(?string $status): ?string
    {
        return match ($status) {
            'hadir' => 'confirmed',
            'izin' => 'excused',
            'alpha' => 'absent',
            'pulang' => 'checked_out',
            default => null,
        };
    }

    private function parseTimestamp(mixed $date, mixed $time): ?CarbonImmutable
    {
        if ($date === null || $time === null || trim((string) $time) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($date.' '.$time, (string) config('attendance.timezone', 'Asia/Jakarta'));
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array{disk: ?string, path: ?string, hash: ?string, mime: ?string, bytes: ?int} */
    private function moveEvidence(?string $filename, int $sourceId): array
    {
        if ($filename === null || $filename === '' || in_array($filename, ['bukti.png', 'siswa.jpg', 'guru.jpg'], true)) {
            return ['disk' => null, 'path' => null, 'hash' => null, 'mime' => null, 'bytes' => null];
        }

        $legacyPath = public_path('presensi_bukti/'.$filename);
        if (! is_file($legacyPath)) {
            return ['disk' => null, 'path' => null, 'hash' => null, 'mime' => null, 'bytes' => null];
        }

        $bytes = file_get_contents($legacyPath);
        if ($bytes === false) {
            return ['disk' => null, 'path' => null, 'hash' => null, 'mime' => null, 'bytes' => null];
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (! in_array($mime, ['image/png', 'image/jpeg'], true)) {
            return ['disk' => null, 'path' => null, 'hash' => null, 'mime' => null, 'bytes' => null];
        }

        $extension = $mime === 'image/png' ? 'png' : 'jpg';
        $path = 'attendance/evidence/migration-'.$sourceId.'.'.$extension;
        $disk = (string) config('attendance.evidence_disk', 'local');
        if (! Storage::disk($disk)->put($path, $bytes)) {
            throw new RuntimeException('Could not move legacy attendance evidence into private storage.');
        }
        $this->migratedEvidencePaths[$legacyPath] = true;

        return [
            'disk' => $disk,
            'path' => $path,
            'hash' => hash('sha256', $bytes),
            'mime' => $mime,
            'bytes' => strlen($bytes),
        ];
    }

    private function removeMigratedEvidence(): void
    {
        foreach (array_keys($this->migratedEvidencePaths) as $path) {
            @unlink($path);
        }
    }

    private function auditAction(?string $action): string
    {
        return match (Str::lower(trim((string) $action))) {
            'tambah', 'create', 'created' => 'created',
            'update', 'updated' => 'updated',
            'hapus', 'delete', 'deleted' => 'deleted',
            default => Str::snake((string) $action) ?: 'legacy_event',
        };
    }

    private function subjectType(?string $table): ?string
    {
        return match ($table) {
            'akun' => 'account',
            'siswa' => 'student',
            'guru' => 'teacher',
            'kelas' => 'classroom',
            'jurusan' => 'department',
            'pengurus_kelas' => 'class_officer',
            'presensi_siswa' => 'attendance_record',
            default => $table === null ? null : Str::snake($table),
        };
    }
};
