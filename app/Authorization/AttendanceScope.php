<?php

namespace App\Authorization;

use App\Models\Akun;
use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;

class AttendanceScope
{
    public function canViewStudent(Akun $account, Siswa $student): bool
    {
        $role = RoleCode::forAccount($account);

        if ($role === RoleCode::STUDENT) {
            return (int) $student->id_akun === (int) $account->getKey();
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
            $student->loadMissing('kelas.waliKelas');
            $class = $student->getRelation('kelas');
            if (! $class instanceof Kelas) {
                return false;
            }

            $homeroomTeacher = $class->getRelation('waliKelas');

            return $homeroomTeacher instanceof Guru
                && (int) $homeroomTeacher->id_akun === (int) $account->getKey();
        }

        return false;
    }

    public function canSubmitFor(Akun $account, Siswa $student): bool
    {
        $role = RoleCode::forAccount($account);

        if ($role === RoleCode::STUDENT) {
            return (int) $student->id_akun === (int) $account->getKey();
        }

        return in_array($role, [
            RoleCode::CLASS_OFFICER,
            RoleCode::DUTY_TEACHER,
            RoleCode::ADMINISTRATION,
        ], true) && $this->canViewStudent($account, $student);
    }

    public function canReview(Akun $account, AttendanceRecord $record): bool
    {
        $student = $record->relationLoaded('student')
            ? $record->student
            : $record->student()->first();

        if (! $student instanceof Siswa) {
            return false;
        }

        $role = RoleCode::forAccount($account);

        if (in_array($role, [RoleCode::DUTY_TEACHER, RoleCode::ADMINISTRATION], true)) {
            return true;
        }

        return $role === RoleCode::HOMEROOM_TEACHER
            && $this->canViewStudent($account, $student);
    }

    public function canReviewEvent(Akun $account, AttendanceEvent $event): bool
    {
        $student = $event->relationLoaded('student')
            ? $event->student
            : $event->student()->first();

        return $student instanceof Siswa
            && in_array(RoleCode::forAccount($account), [RoleCode::DUTY_TEACHER, RoleCode::ADMINISTRATION], true)
            && $this->canViewStudent($account, $student);
    }

    public function canObserve(Akun $account, Siswa $student): bool
    {
        $role = RoleCode::forAccount($account);

        if ($role === RoleCode::DUTY_TEACHER || $role === RoleCode::ADMINISTRATION) {
            return true;
        }

        return $role === RoleCode::CLASS_OFFICER
            && $this->sameClassAsOwnStudent($account, $student);
    }

    private function sameClassAsOwnStudent(Akun $account, Siswa $student): bool
    {
        $ownStudent = Siswa::query()
            ->where('id_akun', $account->getKey())
            ->first(['id_kelas']);

        return $ownStudent instanceof Siswa
            && (int) $ownStudent->id_kelas === (int) $student->id_kelas;
    }
}
