<?php

namespace App\Domain\Attendance;

enum AttendanceState: string
{
    case SUBMITTED = 'submitted';
    case NEEDS_REVIEW = 'needs_review';
    case CONFIRMED = 'confirmed';
    case REJECTED = 'rejected';
    case EXCUSED = 'excused';
    case ABSENT = 'absent';
}
