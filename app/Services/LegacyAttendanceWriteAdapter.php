<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
use App\Models\Validasi;
use Illuminate\Support\Carbon;

class LegacyAttendanceWriteAdapter
{
    public function __construct(private AttendanceSessionCatalog $sessionCatalog) {}

    public function createDailyCapture(
        Siswa $student,
        Carbon $capturedAt,
        string $status,
        string $notes,
        string $actorLabel,
        ?int $officerId = null,
    ): PresensiSiswa {
        $legacy = PresensiSiswa::query()->create([
            'id_siswa' => $student->getKey(),
            // New evidence is private and served through the authorized route.
            'foto_bukti' => '',
            'jam_masuk' => $capturedAt->format('H:i:s'),
            'tanggal' => $capturedAt->toDateString(),
            'status_kehadiran' => $status,
            'keterangan' => $notes,
            'pembuat' => $actorLabel,
        ]);

        foreach ($this->sessionCatalog->validationCodes() as $validationCode) {
            Validasi::query()->updateOrCreate(
                [
                    'id_presensi' => $legacy->getKey(),
                    'waktu_validasi' => $validationCode,
                ],
                [
                    'id_pengurus' => $officerId,
                    'status_validasi' => 'tidak_ada',
                ],
            );
        }

        return $legacy;
    }

    public function link(AttendanceRecord $record, PresensiSiswa $legacy): void
    {
        $record->update(['legacy_presensi_id' => $legacy->getKey()]);
    }
}
