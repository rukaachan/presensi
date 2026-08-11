<?php

namespace App\Policies;

use App\Authorization\AttendanceScope;
use App\Authorization\RoleCode;
use App\Models\Account;
use App\Models\LeaveRequest;
use App\Models\Student;

class LeaveRequestPolicy
{
    public function __construct(private AttendanceScope $scope) {}

    public function view(Account $account, LeaveRequest $request): bool
    {
        $student = $request->relationLoaded('student')
            ? $request->student
            : $request->student()->first();

        return $student instanceof Student && $this->scope->canViewStudent($account, $student);
    }

    public function create(Account $account, LeaveRequest $request): bool
    {
        $student = $request->relationLoaded('student')
            ? $request->student
            : $request->student()->first();

        return $student instanceof Student
            && RoleCode::forAccount($account) === RoleCode::STUDENT
            && (int) $student->account_id === (int) $account->getKey();
    }

    public function review(Account $account, LeaveRequest $request): bool
    {
        $role = RoleCode::forAccount($account);
        $student = $request->relationLoaded('student')
            ? $request->student
            : $request->student()->first();

        return $student instanceof Student
            && in_array($role, [
                RoleCode::HOMEROOM_TEACHER,
                RoleCode::DUTY_TEACHER,
                RoleCode::ADMINISTRATION,
            ], true)
            && $this->scope->canViewStudent($account, $student);
    }

    public function delete(Account $account, LeaveRequest $request): bool
    {
        return false;
    }
}
