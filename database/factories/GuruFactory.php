<?php

namespace Database\Factories;

use App\Models\Akun;
use App\Models\Guru;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guru>
 */
class GuruFactory extends Factory
{
    protected $model = Guru::class;

    public function definition(): array
    {
        return [
            'id_akun' => Akun::factory(),
            'nama_guru' => fake()->name(),
            'foto_guru' => 'guru.jpg',
            'pembuat' => 'Factory',
        ];
    }
}
