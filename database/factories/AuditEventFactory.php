<?php

namespace Database\Factories;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditEvent>
 */
class AuditEventFactory extends Factory
{
    protected $model = AuditEvent::class;

    public function definition(): array
    {
        return [
            'actor_id' => null,
            'actor_type' => null,
            'source_actor' => null,
            'action' => fake()->randomElement(['created', 'updated', 'reviewed']),
            'subject_type' => null,
            'subject_id' => null,
            'before' => null,
            'after' => null,
            'metadata' => [],
            'occurred_at' => now(),
            'source_log_id' => null,
        ];
    }
}
