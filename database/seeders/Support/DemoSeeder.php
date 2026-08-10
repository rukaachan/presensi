<?php

namespace Database\Seeders\Support;

use App\Models\Akun;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

abstract class DemoSeeder extends Seeder
{
    protected function account(string $username, string $roleName): Akun
    {
        return Akun::query()->updateOrCreate(
            ['username' => $username],
            [
                'id_role' => $this->roleId($roleName),
                'password' => Hash::make($this->password()),
            ],
        );
    }

    protected function roleId(string $roleName): int
    {
        $roleId = Role::query()
            ->where('nama_role', $roleName)
            ->value('id_role');

        if ($roleId === null) {
            throw new RuntimeException("Role [{$roleName}] must be seeded before demo data.");
        }

        return (int) $roleId;
    }

    protected function password(): string
    {
        $password = (string) config('demo.seed_password');

        if ($password === '') {
            throw new RuntimeException('DEMO_SEED_PASSWORD must not be empty.');
        }

        return $password;
    }

    protected function creator(): string
    {
        return 'Demo seed';
    }
}
