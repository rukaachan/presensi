<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use Database\Seeders\Support\DemoSeeder;

class AcademicSeeder extends DemoSeeder
{
    public function run(): void
    {
        foreach ([
            ['Rekayasa Perangkat Lunak', 'RPL 1', 'X', 'wali.demo'],
            ['Teknik Komputer dan Jaringan', 'TKJ 1', 'X', 'wali.002'],
            ['Teknik Pengelasan', 'TPL 1', 'XI', 'wali.003'],
            ['Teknik Permesinan', 'TP 1', 'XI', 'wali.004'],
            ['Teknik Kendaraan Ringan dan Otomotif', 'TKR 1', 'XII', 'wali.005'],
            ['Multimedia', 'MM 1', 'XII', 'wali.006'],
            ['Akuntansi', 'AK 1', 'X', 'wali.demo'],
            ['Tata Busana', 'TB 1', 'XI', 'wali.002'],
        ] as [$departmentName, $className, $grade, $teacherUsername]) {
            $department = Jurusan::query()
                ->where('nama_jurusan', $departmentName)
                ->firstOrFail();
            $teacher = Guru::query()
                ->whereHas('akun', static fn ($query) => $query->where('username', $teacherUsername))
                ->firstOrFail();

            Kelas::query()->updateOrCreate(
                [
                    'id_jurusan' => $department->getKey(),
                    'nama_kelas' => $className,
                    'tingkatan' => $grade,
                ],
                [
                    'id_wali_kelas' => $teacher->getKey(),
                    'status_kelas' => 'aktif',
                    'pembuat' => $this->creator(),
                ],
            );
        }
    }
}
