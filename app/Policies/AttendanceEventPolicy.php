<?php

namespace App\Policies;

use App\Authorization\AttendanceScope;
use App\Models\Account;
use App\Models\AttendanceEvent;
use App\Models\Student;

class AttendanceEventPolicy
{
    public function __construct(private AttendanceScope $scope) {}

    public function view(Account $account, AttendanceEvent $event): bool
    {
        $student = $event->relationLoaded('student')
            ? $event->student
            : $event->student()->first();

        return $student instanceof Student && $this->scope->canViewStudent($account, $student);
    }

    public function create(Account $account, AttendanceEvent $event): bool
    {
        $student = $event->relationLoaded('student')
            ? $event->student
            : $event->student()->first();

        return $student instanceof Student && $this->scope->canObserve($account, $student);
    }

    public function update(Account $account, AttendanceEvent $event): bool
    {
        return $this->scope->canReviewEvent($account, $event);
    }

    public function delete(Account $account, AttendanceEvent $event): bool
    {
        return false;
    }
}
