<?php

namespace Database\Factories;

use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kelas>
 */
class KelasFactory extends Factory
{
    protected $model = Kelas::class;

    public function definition(): array
    {
        return [
            'id_wali_kelas' => Guru::factory(),
            'id_jurusan' => Jurusan::factory(),
            'nama_kelas' => 'Kelas '.fake()->unique()->bothify('??##'),
            'tingkatan' => fake()->randomElement(['X', 'XI', 'XII']),
            'status_kelas' => 'aktif',
            'pembuat' => 'Factory',
        ];
    }
}
