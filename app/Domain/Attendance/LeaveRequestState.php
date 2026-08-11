<?php

namespace App\Domain\Attendance;

enum LeaveRequestState: string
{
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
}
