<?php

namespace App\Policies;

use App\Authorization\AttendanceScope;
use App\Authorization\RoleCode;
use App\Models\Akun;
use App\Models\LeaveRequest;
use App\Models\Siswa;

class LeaveRequestPolicy
{
    public function __construct(private AttendanceScope $scope) {}

    public function view(Akun $account, LeaveRequest $request): bool
    {
        $student = $request->relationLoaded('student')
            ? $request->student
            : $request->student()->first();

        return $student instanceof Siswa && $this->scope->canViewStudent($account, $student);
    }

    public function create(Akun $account, LeaveRequest $request): bool
    {
        $student = $request->relationLoaded('student')
            ? $request->student
            : $request->student()->first();

        return $student instanceof Siswa
            && RoleCode::forAccount($account) === RoleCode::STUDENT
            && (int) $student->id_akun === (int) $account->getKey();
    }

    public function review(Akun $account, LeaveRequest $request): bool
    {
        $role = RoleCode::forAccount($account);
        $student = $request->relationLoaded('student')
            ? $request->student
            : $request->student()->first();

        return $student instanceof Siswa
            && in_array($role, [
                RoleCode::HOMEROOM_TEACHER,
                RoleCode::DUTY_TEACHER,
                RoleCode::ADMINISTRATION,
            ], true)
            && $this->scope->canViewStudent($account, $student);
    }

    public function delete(Akun $account, LeaveRequest $request): bool
    {
        return false;
    }
}
