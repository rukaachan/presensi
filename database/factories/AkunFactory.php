<?php

namespace Database\Factories;

use App\Models\Akun;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Akun>
 */
class AkunFactory extends Factory
{
    protected $model = Akun::class;

    public function definition(): array
    {
        return [
            'id_role' => Role::factory(),
            'username' => fake()->unique()->userName(),
            'password' => Hash::make('password'),
        ];
    }
}
