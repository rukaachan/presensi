<?php

namespace App\Models;

use App\Domain\Attendance\LeaveRequestState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $table = 'leave_requests';

    protected $fillable = [
        'student_id',
        'attendance_record_id',
        'state',
        'reason',
        'attachment_disk',
        'attachment_path',
        'submitted_by',
        'reviewed_by',
        'reviewed_at',
        'decision_note',
        'source_letter_id',
    ];

    protected function casts(): array
    {
        return [
            'state' => LeaveRequestState::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class, 'attendance_record_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('state', 'submitted');
    }
}
