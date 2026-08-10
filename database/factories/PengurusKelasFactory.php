<?php

namespace Database\Factories;

use App\Models\PengurusKelas;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PengurusKelas>
 */
class PengurusKelasFactory extends Factory
{
    protected $model = PengurusKelas::class;

    public function definition(): array
    {
        return [
            'id_siswa' => Siswa::factory(),
            'jabatan' => 'Pengurus Kelas',
            'pembuat' => 'Factory',
        ];
    }
}
