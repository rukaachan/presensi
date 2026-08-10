<?php

namespace Database\Seeders;

use App\Models\PengurusKelas;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
use App\Models\Validasi;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
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
                if ($status === 'hadir') {
                    $this->syncValidations(
                        $attendance,
                        $officer,
                        $daysAgo === 0 && ((int) $index % 5 === 0),
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

    private function syncValidations(
        PresensiSiswa $attendance,
        ?PengurusKelas $officer,
        bool $pending,
    ): void {
        foreach ([
            'istirahat_pertama',
            'istirahat_kedua',
            'istirahat_ketiga',
        ] as $period) {
            Validasi::query()->updateOrCreate(
                [
                    'id_presensi' => $attendance->getKey(),
                    'waktu_validasi' => $period,
                ],
                [
                    'id_pengurus' => $officer?->getKey(),
                    'status_validasi' => $pending ? 'tidak_ada' : 'hadir',
                ],
            );
        }
    }

    private function creator(): string
    {
        return 'Demo seed';
    }
}
