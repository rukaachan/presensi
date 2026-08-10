<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\PengurusKelas;
use App\Models\Siswa;
use Carbon\Carbon;
use Database\Seeders\Support\DemoSeeder;

class StudentSeeder extends DemoSeeder
{
    public function run(): void
    {
        $names = [
            'Aditya Nugraha', 'Alya Maharani', 'Bagas Ramadhan',
            'Bima Prakoso', 'Citra Lestari', 'Daffa Maulana',
            'Dinda Aulia', 'Eka Saputra', 'Fauzan Hakim',
            'Gita Permata', 'Hana Kurnia', 'Ilham Fadillah',
            'Jihan Safitri', 'Kamal Hidayat', 'Laras Wulandari',
            'Miko Firmansyah', 'Nabila Putri', 'Oki Setiawan',
            'Putri Ananda', 'Rafi Akbar', 'Salsa Amelia',
            'Tegar Pratama', 'Ulya Rahma', 'Vino Kurniawan',
        ];

        $academicYear = Carbon::now('Asia/Jakarta')->year;
        $sequence = 0;

        foreach (Kelas::query()->orderBy('id_kelas')->get() as $class) {
            for ($slot = 1; $slot <= 3; $slot++) {
                $sequence++;
                $isOfficer = $slot === 1;
                $username = match ($sequence) {
                    1 => 'pengurus.demo',
                    2 => 'siswa.demo',
                    default => $isOfficer
                        ? sprintf('pengurus.%03d', $sequence)
                        : sprintf('siswa.%03d', $sequence),
                };
                $roleName = $isOfficer ? 'Pengurus Kelas' : 'Siswa';
                $account = $this->account($username, $roleName);
                $student = Siswa::query()->updateOrCreate(
                    ['id_akun' => $account->getKey()],
                    [
                        'id_kelas' => $class->getKey(),
                        'nis' => 260000 + $sequence,
                        'nama_siswa' => $names[$sequence - 1],
                        'nomer_hp' => sprintf('08120000%04d', $sequence),
                        'jenis_kelamin' => $sequence % 2 === 0 ? 'perempuan' : 'laki-laki',
                        'angkatan' => $academicYear,
                        'status_siswa' => 'aktif',
                        'status_jabatan' => $isOfficer ? 'ketua_kelas' : 'siswa',
                        'foto_siswa' => 'siswa.jpg',
                        'pembuat' => $this->creator(),
                    ],
                );

                if ($isOfficer) {
                    PengurusKelas::query()->updateOrCreate(
                        ['id_siswa' => $student->getKey()],
                        [
                            'jabatan' => 'Pengurus Kelas',
                            'pembuat' => $this->creator(),
                        ],
                    );
                }
            }
        }
    }
}
