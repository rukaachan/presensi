<?php

namespace App\Models;

use App\Domain\Attendance\AttendanceState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $student_id
 * @property int $attendance_session_id
 * @property \Illuminate\Support\Carbon $event_date
 * @property AttendanceState $state
 * @property string|null $proposed_status
 * @property-read Student|null $student
 * @property-read AttendanceSession|null $session
 */
class AttendanceEvent extends Model
{
    use HasFactory;

    protected $table = 'attendance_events';

    protected $fillable = [
        'student_id',
        'attendance_session_id',
        'event_date',
        'state',
        'proposed_status',
        'observed_at',
        'notes',
        'source',
        'observed_by',
        'reviewed_by',
        'reviewed_at',
        'idempotency_key',
        'source_event_id',
        'source_attendance_id',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'state' => AttendanceState::class,
            'observed_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function observedBy(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'observed_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'reviewed_by');
    }

    public function scopeForDate(Builder $query, Carbon|string $date): Builder
    {
        return $query->whereDate('event_date', $date);
    }
}
