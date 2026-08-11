<?php

namespace App\Authorization;

use App\Models\Account;
use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Teacher;

class AttendanceScope
{
    public function canViewStudent(Account $account, Student $student): bool
    {
        $role = RoleCode::forAccount($account);

        if ($role === RoleCode::STUDENT) {
            return (int) $student->account_id === (int) $account->getKey();
        }

        if (in_array($role, [
            RoleCode::DUTY_TEACHER,
            RoleCode::COUNSELING_TEACHER,
            RoleCode::ADMINISTRATION,
        ], true)) {
            return true;
        }

        if ($role === RoleCode::CLASS_OFFICER) {
            return $this->sameClassAsOwnStudent($account, $student);
        }

        if ($role === RoleCode::HOMEROOM_TEACHER) {
            $student->loadMissing('classroom.homeroomTeacher');
            $classroom = $student->classroom;
            $teacher = $classroom instanceof Classroom ? $classroom->homeroomTeacher : null;

            return $teacher instanceof Teacher
                && (int) $teacher->account_id === (int) $account->getKey();
        }

        return false;
    }

    public function canSubmitFor(Account $account, Student $student): bool
    {
        $role = RoleCode::forAccount($account);

        if ($role === RoleCode::STUDENT) {
            return (int) $student->account_id === (int) $account->getKey();
        }

        return in_array($role, [
            RoleCode::CLASS_OFFICER,
            RoleCode::DUTY_TEACHER,
            RoleCode::ADMINISTRATION,
        ], true) && $this->canViewStudent($account, $student);
    }

    public function canReview(Account $account, AttendanceRecord $record): bool
    {
        $student = $record->relationLoaded('student') ? $record->student : $record->student()->first();
        if (! $student instanceof Student) {
            return false;
        }

        return in_array(RoleCode::forAccount($account), [RoleCode::DUTY_TEACHER, RoleCode::ADMINISTRATION], true)
            || (RoleCode::forAccount($account) === RoleCode::HOMEROOM_TEACHER
                && $this->canViewStudent($account, $student));
    }

    public function canReviewEvent(Account $account, AttendanceEvent $event): bool
    {
        $student = $event->relationLoaded('student') ? $event->student : $event->student()->first();

        return $student instanceof Student
            && in_array(RoleCode::forAccount($account), [RoleCode::DUTY_TEACHER, RoleCode::ADMINISTRATION], true)
            && $this->canViewStudent($account, $student);
    }

    public function canObserve(Account $account, Student $student): bool
    {
        $role = RoleCode::forAccount($account);

        if (in_array($role, [RoleCode::DUTY_TEACHER, RoleCode::ADMINISTRATION], true)) {
            return true;
        }

        return $role === RoleCode::CLASS_OFFICER && $this->sameClassAsOwnStudent($account, $student);
    }

    private function sameClassAsOwnStudent(Account $account, Student $student): bool
    {
        $ownStudent = Student::query()
            ->where('account_id', $account->getKey())
            ->first(['classroom_id']);

        return $ownStudent instanceof Student
            && (int) $ownStudent->classroom_id === (int) $student->classroom_id;
    }
}
