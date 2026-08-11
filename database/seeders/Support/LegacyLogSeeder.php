<?php

namespace Database\Seeders\Support;

use App\Models\Logs;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class LegacyLogSeeder extends Seeder
{
    public function run(): void
    {
        $now = CarbonImmutable::now((string) config('attendance.timezone', 'Asia/Jakarta'));

        $log = Logs::query()
            ->where('tabel', 'seed')
            ->where('aktor', 'System')
            ->whereDate('tanggal', $now->toDateString())
            ->where('aksi', 'Seed')
            ->first();

        if ($log === null) {
            Logs::query()->create([
                'tabel' => 'seed',
                'aktor' => 'System',
                'tanggal' => $now->toDateString(),
                'jam' => '00:00:00',
                'aksi' => 'Seed',
                'record' => 'Reference and attendance compatibility fixtures',
                'status' => 'aktif',
            ]);
        } else {
            $log->update([
                'record' => 'Reference and attendance compatibility fixtures',
                'status' => 'aktif',
            ]);
        }
    }
}
