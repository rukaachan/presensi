<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\GuruBk;
use App\Models\GuruPiket;
use App\Models\TataUsaha;
use Database\Seeders\Support\DemoSeeder;

class StaffSeeder extends DemoSeeder
{
    public function run(): void
    {
        foreach ([
            ['wali.demo', 'Raka Pratama'],
            ['wali.002', 'Dewi Lestari'],
            ['wali.003', 'Bagus Santoso'],
            ['wali.004', 'Nadia Permata'],
            ['wali.005', 'Fajar Hidayat'],
            ['wali.006', 'Sinta Maharani'],
        ] as [$username, $name]) {
            $account = $this->account($username, 'Wali Kelas');

            Guru::query()->updateOrCreate(
                ['id_akun' => $account->getKey()],
                [
                    'nama_guru' => $name,
                    'foto_guru' => 'guru.jpg',
                    'pembuat' => $this->creator(),
                ],
            );
        }

        $piketAccount = $this->account('piket.demo', 'Guru Piket');
        $piketGuru = Guru::query()->updateOrCreate(
            ['id_akun' => $piketAccount->getKey()],
            [
                'nama_guru' => 'Arif Setiawan',
                'foto_guru' => 'guru.jpg',
                'pembuat' => $this->creator(),
            ],
        );
        GuruPiket::query()->updateOrCreate(['id_guru' => $piketGuru->getKey()], []);

        $bkAccount = $this->account('bk.demo', 'Guru BK');
        $bkGuru = Guru::query()->updateOrCreate(
            ['id_akun' => $bkAccount->getKey()],
            [
                'nama_guru' => 'Maya Anggraini',
                'foto_guru' => 'guru.jpg',
                'pembuat' => $this->creator(),
            ],
        );
        GuruBk::query()->updateOrCreate(['id_guru' => $bkGuru->getKey()], []);

        $tuAccount = $this->account('tu.demo', 'Tata Usaha');
        TataUsaha::query()->updateOrCreate(['id_akun' => $tuAccount->getKey()], []);
    }
}
