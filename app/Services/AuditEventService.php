<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AuditEvent;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AuditEventService
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>|null  $metadata
     */
    public function record(
        string $action,
        Model|string|null $subject = null,
        ?Account $actor = null,
        ?array $before = null,
        ?array $after = null,
        ?array $metadata = null,
        ?DateTimeInterface $occurredAt = null,
    ): AuditEvent {
        return AuditEvent::query()->create([
            'actor_id' => $actor?->getKey(),
            'actor_type' => $actor === null ? null : 'account',
            'action' => $action,
            'subject_type' => $subject instanceof Model
                ? Str::singular($subject->getTable())
                : $subject,
            'subject_id' => $subject instanceof Model ? (string) $subject->getKey() : null,
            'before' => $before,
            'after' => $after,
            'metadata' => $metadata,
            'occurred_at' => $occurredAt === null
                ? now()
                : Carbon::instance($occurredAt),
        ]);
    }
}
