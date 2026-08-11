<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditEvent extends Model
{
    use HasFactory;

    protected $table = 'audit_events';

    protected $fillable = [
        'actor_id',
        'actor_type',
        'source_actor',
        'action',
        'subject_type',
        'subject_id',
        'before',
        'after',
        'metadata',
        'occurred_at',
        'source_log_id',
    ];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Audit events are append-only.');
        });
        static::deleting(static function (): never {
            throw new LogicException('Audit events are append-only.');
        });
    }

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'actor_id');
    }
}
