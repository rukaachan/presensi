<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        $this->createAuditTriggers(
            $isSqlite ? "date('now')" : 'CURDATE()',
            $isSqlite ? "time('now')" : 'CURTIME()'
        );
        $this->createValidationTrigger($isSqlite);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ([
            'add_siswa',
            'update_siswa',
            'delete_siswa',
            'add_pengurus',
            'update_presensi_siswa',
            'update_pengurus',
            'delete_pengurus',
            'add_guru',
            'update_guru',
            'delete_guru',
            'add_jurusan',
            'update_jurusan',
            'delete_jurusan',
            'add_kelas',
            'update_kelas',
            'delete_kelas',
            'insert_validasi',
        ] as $trigger) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
        }
    }

    private function createAuditTriggers(string $dateExpression, string $timeExpression): void
    {
        $triggers = [
            ['name' => 'add_siswa', 'timing' => 'BEFORE INSERT', 'table' => 'siswa', 'logTable' => 'siswa', 'actor' => 'NEW.pembuat', 'action' => 'Tambah'],
            ['name' => 'update_siswa', 'timing' => 'AFTER UPDATE', 'table' => 'siswa', 'logTable' => 'siswa', 'actor' => 'NEW.pembuat', 'action' => 'Update'],
            ['name' => 'update_presensi_siswa', 'timing' => 'AFTER UPDATE', 'table' => 'presensi_siswa', 'logTable' => 'presensi_siswa', 'actor' => 'NEW.pembuat', 'action' => 'Update'],
            ['name' => 'delete_siswa', 'timing' => 'BEFORE DELETE', 'table' => 'siswa', 'logTable' => 'siswa', 'actor' => 'OLD.pembuat', 'action' => 'Hapus'],
            ['name' => 'add_pengurus', 'timing' => 'BEFORE INSERT', 'table' => 'pengurus_kelas', 'logTable' => 'pengurus_kelas', 'actor' => 'NEW.pembuat', 'action' => 'Tambah'],
            ['name' => 'update_pengurus', 'timing' => 'AFTER UPDATE', 'table' => 'pengurus_kelas', 'logTable' => 'pengurus_kelas', 'actor' => 'NEW.pembuat', 'action' => 'Update'],
            ['name' => 'delete_pengurus', 'timing' => 'AFTER DELETE', 'table' => 'pengurus_kelas', 'logTable' => 'pengurus_kelas', 'actor' => 'OLD.pembuat', 'action' => 'Hapus'],
            ['name' => 'add_guru', 'timing' => 'BEFORE INSERT', 'table' => 'guru', 'logTable' => 'guru', 'actor' => 'NEW.pembuat', 'action' => 'Tambah'],
            ['name' => 'update_guru', 'timing' => 'AFTER UPDATE', 'table' => 'guru', 'logTable' => 'pengurus', 'actor' => 'NEW.pembuat', 'action' => 'Update'],
            ['name' => 'delete_guru', 'timing' => 'BEFORE DELETE', 'table' => 'guru', 'logTable' => 'guru', 'actor' => 'OLD.pembuat', 'action' => 'Hapus'],
            ['name' => 'add_jurusan', 'timing' => 'BEFORE INSERT', 'table' => 'jurusan', 'logTable' => 'jurusan', 'actor' => 'NEW.pembuat', 'action' => 'Tambah'],
            ['name' => 'update_jurusan', 'timing' => 'AFTER UPDATE', 'table' => 'jurusan', 'logTable' => 'jurusan', 'actor' => 'NEW.pembuat', 'action' => 'Update'],
            ['name' => 'delete_jurusan', 'timing' => 'AFTER DELETE', 'table' => 'jurusan', 'logTable' => 'jurusan', 'actor' => 'OLD.pembuat', 'action' => 'Hapus'],
            ['name' => 'add_kelas', 'timing' => 'BEFORE INSERT', 'table' => 'kelas', 'logTable' => 'kelas', 'actor' => 'NEW.pembuat', 'action' => 'Tambah'],
            ['name' => 'update_kelas', 'timing' => 'AFTER UPDATE', 'table' => 'kelas', 'logTable' => 'kelas', 'actor' => 'NEW.pembuat', 'action' => 'Update'],
            ['name' => 'delete_kelas', 'timing' => 'AFTER DELETE', 'table' => 'kelas', 'logTable' => 'kelas', 'actor' => 'OLD.pembuat', 'action' => 'Hapus'],
        ];

        foreach ($triggers as $trigger) {
            DB::unprepared(sprintf(
                "CREATE TRIGGER %s %s ON %s FOR EACH ROW
                BEGIN
                    INSERT INTO logs (tabel, aktor, tanggal, jam, aksi, record)
                    VALUES ('%s', %s, %s, %s, '%s', 'Sukses');
                END",
                $trigger['name'],
                $trigger['timing'],
                $trigger['table'],
                $trigger['logTable'],
                $trigger['actor'],
                $dateExpression,
                $timeExpression,
                $trigger['action']
            ));
        }
    }

    private function createValidationTrigger(bool $isSqlite): void
    {
        if ($isSqlite) {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER insert_validasi
                AFTER INSERT ON presensi_siswa
                FOR EACH ROW
                WHEN NEW.status_kehadiran = 'hadir'
                BEGIN
                    INSERT INTO validasi (id_pengurus, id_presensi, status_validasi, waktu_validasi)
                        VALUES (NULL, NEW.id_presensi, 'tidak_ada', 'istirahat_pertama');
                    INSERT INTO validasi (id_pengurus, id_presensi, status_validasi, waktu_validasi)
                        VALUES (NULL, NEW.id_presensi, 'tidak_ada', 'istirahat_kedua');
                    INSERT INTO validasi (id_pengurus, id_presensi, status_validasi, waktu_validasi)
                        VALUES (NULL, NEW.id_presensi, 'tidak_ada', 'istirahat_ketiga');
                END
            SQL);

            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER insert_validasi
            AFTER INSERT ON presensi_siswa
            FOR EACH ROW
            BEGIN
                IF NEW.status_kehadiran = 'hadir' THEN
                    INSERT INTO validasi (id_pengurus, id_presensi, status_validasi, waktu_validasi)
                        VALUES (NULL, NEW.id_presensi, 'tidak_ada', 'istirahat_pertama');
                    INSERT INTO validasi (id_pengurus, id_presensi, status_validasi, waktu_validasi)
                        VALUES (NULL, NEW.id_presensi, 'tidak_ada', 'istirahat_kedua');
                    INSERT INTO validasi (id_pengurus, id_presensi, status_validasi, waktu_validasi)
                        VALUES (NULL, NEW.id_presensi, 'tidak_ada', 'istirahat_ketiga');
                END IF;
            END
        SQL);
    }
};
