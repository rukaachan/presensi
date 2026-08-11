<?php

namespace Database\Seeders;

use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\PengurusKelas;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
use App\Models\Validasi;
use App\Services\AttendanceSessionCatalog;
use App\Services\LegacyAttendanceAdapter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AttendanceSeeder extends Seeder
{
    public function run(AttendanceSessionCatalog $sessionCatalog, LegacyAttendanceAdapter $legacyStatuses): void
    {
        $timezone = (string) config('attendance.timezone', 'Asia/Jakarta');
        $validationCodes = $sessionCatalog->validationCodes();
        $requiredSession = $sessionCatalog->required();
        $targetTablesAvailable = Schema::hasTable('attendance_records')
            && Schema::hasTable('attendance_events');
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $students = Siswa::query()
            ->orderBy('id_kelas')
            ->orderBy('id_siswa')
            ->get();
        $officersByClass = PengurusKelas::query()
            ->with('siswa')
            ->get()
            ->filter(static fn (PengurusKelas $officer): bool => $officer->siswa !== null)
            ->keyBy(static fn (PengurusKelas $officer): string => (string) $officer->siswa->id_kelas);

        foreach ($students as $index => $student) {
            for ($daysAgo = 0; $daysAgo < 7; $daysAgo++) {
                $status = $this->statusFor((int) $index, $daysAgo);
                $date = $today->subDays($daysAgo)->toDateString();

                if ($status === null) {
                    PresensiSiswa::query()
                        ->where('id_siswa', $student->getKey())
                        ->whereDate('tanggal', $date)
                        ->delete();

                    continue;
                }

                $attendance = PresensiSiswa::query()->updateOrCreate(
                    [
                        'id_siswa' => $student->getKey(),
                        'tanggal' => $date,
                    ],
                    [
                        'foto_bukti' => 'bukti.png',
                        'jam_masuk' => '07:00:00',
                        'status_kehadiran' => $status,
                        'keterangan' => $status === 'hadir' ? 'Presensi demo' : 'Perlu tindak lanjut',
                        'pembuat' => $this->creator(),
                    ],
                );

                $officer = $officersByClass->get((string) $student->id_kelas);
                if ($targetTablesAvailable && $requiredSession !== null) {
                    $this->syncTargetRecord($attendance, $requiredSession, $legacyStatuses, $timezone);
                }

                if ($status === 'hadir') {
                    $this->syncValidations(
                        $attendance,
                        $officer,
                        $daysAgo === 0 && ((int) $index % 5 === 0),
                        $validationCodes,
                        $sessionCatalog,
                        $legacyStatuses,
                        $targetTablesAvailable,
                    );
                } else {
                    Validasi::query()
                        ->where('id_presensi', $attendance->getKey())
                        ->delete();
                }
            }
        }
    }

    private function statusFor(int $studentIndex, int $daysAgo): ?string
    {
        if ($daysAgo === 0) {
            return [
                'hadir', 'hadir', 'izin', 'alpha',
                'hadir', 'hadir', 'hadir', null,
            ][$studentIndex % 8];
        }

        return [
            'hadir', 'hadir', 'hadir', 'izin',
            'hadir', 'alpha', 'hadir',
        ][($studentIndex + $daysAgo) % 7];
    }

    /**
     * @param  list<string>  $validationCodes
     */
    private function syncValidations(
        PresensiSiswa $attendance,
        ?PengurusKelas $officer,
        bool $pending,
        array $validationCodes,
        AttendanceSessionCatalog $sessionCatalog,
        LegacyAttendanceAdapter $legacyStatuses,
        bool $targetTablesAvailable,
    ): void {
        foreach ($validationCodes as $period) {
            $validation = Validasi::query()->updateOrCreate(
                [
                    'id_presensi' => $attendance->getKey(),
                    'waktu_validasi' => $period,
                ],
                [
                    'id_pengurus' => $officer?->getKey(),
                    'status_validasi' => $pending ? 'tidak_ada' : 'hadir',
                ],
            );

            if (! $targetTablesAvailable) {
                continue;
            }

            $session = $sessionCatalog->active()->firstWhere('legacy_code', $period);
            if ($session === null) {
                continue;
            }

            $event = AttendanceEvent::query()
                ->where('student_id', $attendance->getAttribute('id_siswa'))
                ->where('attendance_session_id', $session->getKey())
                ->whereDate('event_date', $attendance->getRawOriginal('tanggal'))
                ->first();
            $attributes = [
                'student_id' => $attendance->getAttribute('id_siswa'),
                'attendance_session_id' => $session->getKey(),
                'event_date' => $attendance->getRawOriginal('tanggal'),
                'state' => $legacyStatuses->stateFromValidationStatus($validation->status_validasi),
                'proposed_status' => $validation->status_validasi === 'tidak_ada'
                    ? null
                    : $validation->status_validasi,
                'observed_at' => null,
                'notes' => null,
                'source' => 'legacy',
                'observed_by' => null,
                'idempotency_key' => 'legacy-validasi:'.$validation->getKey(),
                'legacy_validasi_id' => $validation->getKey(),
                'legacy_presensi_id' => $attendance->getKey(),
            ];
            if ($event === null) {
                AttendanceEvent::query()->create($attributes);
            } else {
                $event->update($attributes);
            }
        }
    }

    private function syncTargetRecord(
        PresensiSiswa $attendance,
        AttendanceSession $session,
        LegacyAttendanceAdapter $legacyStatuses,
        string $timezone,
    ): void {
        $date = (string) $attendance->getRawOriginal('tanggal');
        $record = AttendanceRecord::query()
            ->where('student_id', $attendance->getAttribute('id_siswa'))
            ->where('attendance_session_id', $session->getKey())
            ->whereDate('attendance_date', $date)
            ->first();
        $attributes = [
            'student_id' => $attendance->getAttribute('id_siswa'),
            'attendance_session_id' => $session->getKey(),
            'attendance_date' => $date,
            'state' => $legacyStatuses->stateFromAttendanceStatus((string) $attendance->getAttribute('status_kehadiran')),
            'late' => false,
            'captured_at' => CarbonImmutable::parse(
                $date.' '.(string) $attendance->getAttribute('jam_masuk'),
                $timezone,
            ),
            'evidence_disk' => null,
            'evidence_path' => null,
            'evidence_hash' => null,
            'evidence_mime' => null,
            'evidence_bytes' => null,
            'notes' => $attendance->getAttribute('keterangan'),
            'source' => 'legacy',
            'created_by' => null,
            'updated_by' => null,
            'idempotency_key' => 'legacy-presensi:'.$attendance->getKey(),
            'legacy_presensi_id' => $attendance->getKey(),
        ];

        if ($record === null) {
            AttendanceRecord::query()->create($attributes);
        } else {
            $record->update($attributes);
        }
    }

    private function creator(): string
    {
        return 'Demo seed';
    }
}
