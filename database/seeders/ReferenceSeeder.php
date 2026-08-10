<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ([
                'Siswa',
                'Wali Kelas',
                'Pengurus Kelas',
                'Guru Piket',
                'Guru BK',
                'Tata Usaha',
            ] as $roleName) {
                Role::query()->updateOrCreate(
                    ['nama_role' => $roleName],
                    [],
                );
            }

            foreach ([
                'Rekayasa Perangkat Lunak',
                'Teknik Komputer dan Jaringan',
                'Teknik Pengelasan',
                'Teknik Permesinan',
                'Teknik Kendaraan Ringan dan Otomotif',
                'Multimedia',
                'Akuntansi',
                'Tata Busana',
            ] as $departmentName) {
                Jurusan::query()->updateOrCreate(
                    ['nama_jurusan' => $departmentName],
                    ['pembuat' => 'Reference seed'],
                );
            }
        });
    }
}
