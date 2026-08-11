<?php

namespace App\Services;

use InvalidArgumentException;

class LegacyAttendanceAdapter
{
    private const ATTENDANCE_STATES = [
        'hadir' => 'confirmed',
        'izin' => 'needs_review',
        'alpha' => 'absent',
    ];

    private const VALIDATION_STATES = [
        'tidak_ada' => 'needs_review',
        'hadir' => 'confirmed',
        'izin' => 'needs_review',
        'alpha' => 'absent',
        'pulang' => 'confirmed',
    ];

    public function stateFromAttendanceStatus(?string $status): string
    {
        return self::ATTENDANCE_STATES[$status ?? ''] ?? 'needs_review';
    }

    public function stateFromValidationStatus(?string $status): string
    {
        return self::VALIDATION_STATES[$status ?? ''] ?? 'needs_review';
    }

    public function reviewStateFromAttendanceStatus(?string $status): string
    {
        return match ($status) {
            'hadir' => 'confirmed',
            'izin' => 'excused',
            'alpha' => 'absent',
            default => 'needs_review',
        };
    }

    public function legacyStatusFromState(string $state): string
    {
        return match ($state) {
            'confirmed' => 'hadir',
            'excused' => 'izin',
            'absent' => 'alpha',
            default => throw new InvalidArgumentException("State [{$state}] has no legacy attendance status."),
        };
    }

    public function legacyValidationStatusFromState(string $state): string
    {
        return match ($state) {
            'confirmed' => 'hadir',
            'excused' => 'izin',
            'absent' => 'alpha',
            default => 'tidak_ada',
        };
    }
}
