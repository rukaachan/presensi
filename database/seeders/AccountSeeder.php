<?php

namespace Database\Seeders;

use Database\Seeders\Support\DemoSeeder;

class AccountSeeder extends DemoSeeder
{
    public function run(): void
    {
        foreach ([
            ['administrator.demo', 'administrator'],
            ['homeroom.demo', 'homeroom_teacher'],
            ['duty.demo', 'duty_teacher'],
            ['counseling.demo', 'counseling_teacher'],
            ['student.demo', 'student'],
            ['officer.demo', 'class_officer'],
        ] as [$username, $roleCode]) {
            $this->account($username, $roleCode);
        }
    }
}
