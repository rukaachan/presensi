<?php

namespace App\Models;

use App\Domain\Attendance\AttendanceState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $student_id
 * @property int $attendance_session_id
 * @property \Illuminate\Support\Carbon $attendance_date
 * @property AttendanceState $state
 * @property string|null $evidence_path
 * @property string|null $notes
 * @property-read Student|null $student
 * @property-read AttendanceSession|null $session
 */
class AttendanceRecord extends Model
{
    use HasFactory;

    protected $table = 'attendance_records';

    protected $fillable = [
        'student_id',
        'attendance_session_id',
        'attendance_date',
        'state',
        'late',
        'captured_at',
        'evidence_disk',
        'evidence_path',
        'evidence_hash',
        'evidence_mime',
        'evidence_bytes',
        'notes',
        'source',
        'created_by',
        'updated_by',
        'idempotency_key',
        'source_record_id',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'state' => AttendanceState::class,
            'late' => 'boolean',
            'captured_at' => 'datetime',
            'evidence_bytes' => 'integer',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'updated_by');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'attendance_record_id');
    }

    public function scopeForDate(Builder $query, Carbon|string $date): Builder
    {
        return $query->whereDate('attendance_date', $date);
    }
}
