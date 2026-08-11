<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Department;
use App\Models\Teacher;
use Database\Seeders\Support\DemoSeeder;

class AcademicSeeder extends DemoSeeder
{
    public function run(): void
    {
        foreach ([
            ['Rekayasa Perangkat Lunak', 'RPL 1', 'X', 'homeroom.demo'],
            ['Teknik Komputer dan Jaringan', 'TKJ 1', 'X', 'homeroom.002'],
            ['Teknik Pengelasan', 'TPL 1', 'XI', 'homeroom.003'],
            ['Teknik Permesinan', 'TP 1', 'XI', 'homeroom.004'],
            ['Teknik Kendaraan Ringan dan Otomotif', 'TKR 1', 'XII', 'homeroom.005'],
            ['Multimedia', 'MM 1', 'XII', 'homeroom.006'],
            ['Akuntansi', 'AK 1', 'X', 'homeroom.demo'],
            ['Tata Busana', 'TB 1', 'XI', 'homeroom.002'],
        ] as [$departmentName, $className, $grade, $teacherUsername]) {
            $department = Department::query()->where('name', $departmentName)->firstOrFail();
            $teacher = Teacher::query()
                ->whereHas('account', static fn ($query) => $query->where('username', $teacherUsername))
                ->firstOrFail();

            Classroom::query()->updateOrCreate(
                [
                    'department_id' => $department->getKey(),
                    'name' => $className,
                    'grade_level' => $grade,
                ],
                [
                    'homeroom_teacher_id' => $teacher->getKey(),
                    'status' => 'active',
                    'created_by_label' => $this->creator(),
                ],
            );
        }
    }
}
