<?php

namespace App\Policies;

use App\Authorization\AttendanceScope;
use App\Models\Account;
use App\Models\AttendanceRecord;
use App\Models\Student;

class AttendanceRecordPolicy
{
    public function __construct(private AttendanceScope $scope) {}

    public function view(Account $account, AttendanceRecord $record): bool
    {
        $student = $record->relationLoaded('student')
            ? $record->student
            : $record->student()->first();

        return $student instanceof Student && $this->scope->canViewStudent($account, $student);
    }

    public function create(Account $account, AttendanceRecord $record): bool
    {
        $student = $record->relationLoaded('student')
            ? $record->student
            : $record->student()->first();

        return $student instanceof Student && $this->scope->canSubmitFor($account, $student);
    }

    public function update(Account $account, AttendanceRecord $record): bool
    {
        return $this->scope->canReview($account, $record);
    }

    public function review(Account $account, AttendanceRecord $record): bool
    {
        return $this->scope->canReview($account, $record);
    }

    public function delete(Account $account, AttendanceRecord $record): bool
    {
        return false;
    }
}
