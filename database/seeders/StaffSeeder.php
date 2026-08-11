<?php

namespace Database\Seeders;

use App\Models\AdministrationStaff;
use App\Models\CounselingTeacher;
use App\Models\DutyTeacher;
use App\Models\Teacher;
use Database\Seeders\Support\DemoSeeder;

class StaffSeeder extends DemoSeeder
{
    public function run(): void
    {
        foreach ([
            ['homeroom.demo', 'Raka Pratama'],
            ['homeroom.002', 'Dewi Lestari'],
            ['homeroom.003', 'Bagus Santoso'],
            ['homeroom.004', 'Nadia Permata'],
            ['homeroom.005', 'Fajar Hidayat'],
            ['homeroom.006', 'Sinta Maharani'],
        ] as [$username, $name]) {
            $account = $this->account($username, 'homeroom_teacher');

            Teacher::query()->updateOrCreate(
                ['account_id' => $account->getKey()],
                [
                    'name' => $name,
                    'photo_path' => 'teacher.jpg',
                    'created_by_label' => $this->creator(),
                ],
            );
        }

        $piketAccount = $this->account('duty.demo', 'duty_teacher');
        $piketTeacher = Teacher::query()->updateOrCreate(
            ['account_id' => $piketAccount->getKey()],
            [
                'name' => 'Arif Setiawan',
                'photo_path' => 'teacher.jpg',
                'created_by_label' => $this->creator(),
            ],
        );
        DutyTeacher::query()->updateOrCreate(['teacher_id' => $piketTeacher->getKey()]);

        $bkAccount = $this->account('counseling.demo', 'counseling_teacher');
        $bkTeacher = Teacher::query()->updateOrCreate(
            ['account_id' => $bkAccount->getKey()],
            [
                'name' => 'Maya Anggraini',
                'photo_path' => 'teacher.jpg',
                'created_by_label' => $this->creator(),
            ],
        );
        CounselingTeacher::query()->updateOrCreate(['teacher_id' => $bkTeacher->getKey()]);

        $tuAccount = $this->account('administrator.demo', 'administrator');
        AdministrationStaff::query()->updateOrCreate(
            ['account_id' => $tuAccount->getKey()],
            ['name' => 'Tata usaha', 'photo_path' => null],
        );
    }
}
