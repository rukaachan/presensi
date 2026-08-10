<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed reference data everywhere and demo data only in safe environments.
     */
    public function run(): void
    {
        if (app()->environment(['local', 'testing'])) {
            $this->call(DemoDatabaseSeeder::class);

            return;
        }

        $this->call(ReferenceSeeder::class);
    }
}
