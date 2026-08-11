<?php

namespace Database\Seeders\Support;

use App\Models\AuditEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AuditFixtureSeeder extends Seeder
{
    public function run(): void
    {
        $now = CarbonImmutable::now((string) config('attendance.timezone', 'Asia/Jakarta'));
        $event = AuditEvent::query()
            ->where('action', 'seeded')
            ->whereDate('occurred_at', $now->toDateString())
            ->first();

        if ($event === null) {
            AuditEvent::query()->create([
                'actor_id' => null,
                'actor_type' => 'system',
                'source_actor' => null,
                'action' => 'seeded',
                'subject_type' => 'fixture',
                'subject_id' => null,
                'before' => null,
                'after' => null,
                'metadata' => ['message' => 'Reference and attendance fixtures'],
                'occurred_at' => $now->startOfDay(),
                'source_log_id' => null,
            ]);
        }
    }
}
