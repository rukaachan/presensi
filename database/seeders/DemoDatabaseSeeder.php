<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use LogicException;

class DemoDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo data may only be seeded in local or testing environments.');
        }

        $this->call([
            ReferenceSeeder::class,
            AccountSeeder::class,
            StaffSeeder::class,
            AcademicSeeder::class,
            StudentSeeder::class,
            AttendanceSeeder::class,
        ]);
    }
}
