<?php

namespace App\Services;

use App\Models\PresensiSiswa;
use App\Models\Validasi;
use Carbon\CarbonImmutable;

class LegacyAttendanceReadAdapter
{
    public function __construct(private LegacyAttendanceAdapter $statuses) {}

    /**
     * @return array<string, mixed>
     */
    public function recordAttributes(PresensiSiswa $legacy, int $sessionId): array
    {
        $rawPhoto = (string) $legacy->getAttribute('foto_bukti');
        $date = $legacy->getRawOriginal('tanggal');
        $time = $legacy->getRawOriginal('jam_masuk');
        $capturedAt = $this->capturedAt($date, $time);

        return [
            'student_id' => $legacy->getAttribute('id_siswa'),
            'attendance_session_id' => $sessionId,
            'attendance_date' => $date,
            'state' => $this->statuses->stateFromAttendanceStatus((string) $legacy->getAttribute('status_kehadiran')),
            'late' => false,
            'captured_at' => $capturedAt,
            'evidence_disk' => $this->isPlaceholder($rawPhoto) ? null : 'public',
            'evidence_path' => $this->isPlaceholder($rawPhoto) ? null : 'presensi_bukti/'.$rawPhoto,
            'notes' => $legacy->getAttribute('keterangan'),
            'source' => 'legacy',
            'legacy_presensi_id' => $legacy->getKey(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function eventAttributes(Validasi $legacy, int $studentId, int $sessionId): array
    {
        $attendance = $legacy->presensiSiswa;
        $eventDate = $attendance instanceof PresensiSiswa
            ? $attendance->getRawOriginal('tanggal')
            : null;

        return [
            'student_id' => $studentId,
            'attendance_session_id' => $sessionId,
            'event_date' => $eventDate,
            'state' => $this->statuses->stateFromValidationStatus((string) $legacy->getAttribute('status_validasi')),
            'source' => 'legacy',
            'observed_by' => null,
            'legacy_validasi_id' => $legacy->getKey(),
            'legacy_presensi_id' => $legacy->id_presensi,
        ];
    }

    private function capturedAt(mixed $date, mixed $time): ?CarbonImmutable
    {
        if ($date === null || $time === null || trim((string) $time) === '') {
            return null;
        }

        return CarbonImmutable::parse(
            sprintf('%s %s', (string) $date, (string) $time),
            (string) config('attendance.timezone', 'Asia/Jakarta'),
        );
    }

    private function isPlaceholder(string $photo): bool
    {
        return $photo === '' || in_array($photo, ['bukti.png', 'siswa.jpg', 'guru.jpg'], true);
    }
}
