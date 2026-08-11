<?php

namespace App\Authorization;

use App\Models\Account;
use App\Models\Role;

enum RoleCode: string
{
    case STUDENT = 'student';
    case HOMEROOM_TEACHER = 'homeroom_teacher';
    case CLASS_OFFICER = 'class_officer';
    case DUTY_TEACHER = 'duty_teacher';
    case COUNSELING_TEACHER = 'counseling_teacher';
    case ADMINISTRATION = 'administrator';

    public static function forAccount(Account $account): ?self
    {
        $role = $account->relationLoaded('role') ? $account->role : $account->role()->first();

        return $role instanceof Role ? self::tryFrom((string) $role->code) : null;
    }
}
