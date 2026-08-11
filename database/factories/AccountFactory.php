<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<Account> */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'role_id' => Role::factory(),
            'username' => fake()->unique()->userName(),
            'password' => Hash::make('password'),
        ];
    }
}
