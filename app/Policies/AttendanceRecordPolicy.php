<?php

namespace App\Policies;

use App\Authorization\AttendanceScope;
use App\Models\Akun;
use App\Models\AttendanceRecord;
use App\Models\Siswa;

class AttendanceRecordPolicy
{
    public function __construct(private AttendanceScope $scope) {}

    public function view(Akun $account, AttendanceRecord $record): bool
    {
        $student = $record->relationLoaded('student')
            ? $record->student
            : $record->student()->first();

        return $student instanceof Siswa && $this->scope->canViewStudent($account, $student);
    }

    public function create(Akun $account, AttendanceRecord $record): bool
    {
        $student = $record->relationLoaded('student')
            ? $record->student
            : $record->student()->first();

        return $student instanceof Siswa && $this->scope->canSubmitFor($account, $student);
    }

    public function update(Akun $account, AttendanceRecord $record): bool
    {
        return $this->scope->canReview($account, $record);
    }

    public function review(Akun $account, AttendanceRecord $record): bool
    {
        return $this->scope->canReview($account, $record);
    }

    public function delete(Akun $account, AttendanceRecord $record): bool
    {
        return false;
    }
}
