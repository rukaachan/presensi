<?php

namespace Database\Factories;

use App\Models\Akun;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Siswa>
 */
class SiswaFactory extends Factory
{
    protected $model = Siswa::class;

    public function definition(): array
    {
        return [
            'id_akun' => Akun::factory(),
            'id_kelas' => Kelas::factory(),
            'nis' => fake()->unique()->numberBetween(100000, 999999),
            'nama_siswa' => fake()->name(),
            'nomer_hp' => fake()->numerify('0812########'),
            'jenis_kelamin' => fake()->randomElement(['laki-laki', 'perempuan']),
            'angkatan' => now('Asia/Jakarta')->year,
            'status_siswa' => 'aktif',
            'status_jabatan' => 'siswa',
            'foto_siswa' => 'siswa.jpg',
            'pembuat' => 'Factory',
        ];
    }
}
