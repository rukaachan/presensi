<?php

namespace Database\Seeders;

use Database\Seeders\Support\DemoSeeder;

class AccountSeeder extends DemoSeeder
{
    public function run(): void
    {
        foreach ([
            ['tu.demo', 'Tata Usaha'],
            ['wali.demo', 'Wali Kelas'],
            ['piket.demo', 'Guru Piket'],
            ['bk.demo', 'Guru BK'],
            ['siswa.demo', 'Siswa'],
            ['pengurus.demo', 'Pengurus Kelas'],
        ] as [$username, $roleName]) {
            $this->account($username, $roleName);
        }
    }
}
