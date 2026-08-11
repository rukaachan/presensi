<?php

namespace Database\Seeders\Support;

use App\Models\Account;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

abstract class DemoSeeder extends Seeder
{
    protected function account(string $username, string $roleCode): Account
    {
        return Account::query()->updateOrCreate(
            ['username' => $username],
            [
                'role_id' => $this->roleId($roleCode),
                'password' => Hash::make($this->password()),
            ],
        );
    }

    protected function roleId(string $roleCode): int
    {
        $roleId = Role::query()->where('code', $roleCode)->value('id');

        if ($roleId === null) {
            throw new RuntimeException("Role [{$roleCode}] must be seeded before demo data.");
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
