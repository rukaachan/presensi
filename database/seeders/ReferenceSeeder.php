<?php

namespace Database\Seeders;

use App\Models\AttendanceSession;
use App\Models\Department;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ([
                ['student', 'Siswa'],
                ['homeroom_teacher', 'Wali kelas'],
                ['class_officer', 'Pengurus kelas'],
                ['duty_teacher', 'Guru piket'],
                ['counseling_teacher', 'Guru BK'],
                ['administrator', 'Tata usaha'],
            ] as [$code, $name]) {
                Role::query()->updateOrCreate(['code' => $code], ['name' => $name]);
            }

            foreach (config('attendance.sessions', []) as $session) {
                AttendanceSession::query()->updateOrCreate(
                    ['code' => $session['code']],
                    [
                        'label' => $session['label'],
                        'kind' => $session['kind'],
                        'required' => $session['required'],
                        'active' => $session['active'],
                        'window_start' => $session['window_start'],
                        'window_end' => $session['window_end'],
                        'sort_order' => $session['sort_order'],
                        'settings' => $session['settings'],
                    ],
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
                Department::query()->updateOrCreate(
                    ['name' => $departmentName],
                    ['created_by_label' => 'Reference seed'],
                );
            }
        });
    }
}
