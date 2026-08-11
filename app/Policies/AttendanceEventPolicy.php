<?php

namespace App\Policies;

use App\Authorization\AttendanceScope;
use App\Models\Akun;
use App\Models\AttendanceEvent;
use App\Models\Siswa;

class AttendanceEventPolicy
{
    public function __construct(private AttendanceScope $scope) {}

    public function view(Akun $account, AttendanceEvent $event): bool
    {
        $student = $event->relationLoaded('student')
            ? $event->student
            : $event->student()->first();

        return $student instanceof Siswa && $this->scope->canViewStudent($account, $student);
    }

    public function create(Akun $account, AttendanceEvent $event): bool
    {
        $student = $event->relationLoaded('student')
            ? $event->student
            : $event->student()->first();

        return $student instanceof Siswa && $this->scope->canObserve($account, $student);
    }

    public function update(Akun $account, AttendanceEvent $event): bool
    {
        return $this->scope->canReviewEvent($account, $event);
    }

    public function delete(Akun $account, AttendanceEvent $event): bool
    {
        return false;
    }
}
