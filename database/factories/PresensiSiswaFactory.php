<?php

namespace Database\Factories;

use App\Models\PresensiSiswa;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PresensiSiswa>
 */
class PresensiSiswaFactory extends Factory
{
    protected $model = PresensiSiswa::class;

    public function definition(): array
    {
        return [
            'id_siswa' => Siswa::factory(),
            'foto_bukti' => 'bukti.png',
            'jam_masuk' => '07:00:00',
            'tanggal' => fake()->unique()->dateTimeBetween('-30 days', 'today')->format('Y-m-d'),
            'status_kehadiran' => 'hadir',
            'keterangan' => 'Factory attendance',
            'pembuat' => 'Factory',
        ];
    }
}
