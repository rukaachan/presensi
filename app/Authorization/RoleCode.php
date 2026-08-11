<?php

namespace App\Authorization;

use App\Models\Akun;
use App\Models\Role;
use Illuminate\Support\Str;

enum RoleCode: string
{
    case STUDENT = 'siswa';
    case HOMEROOM_TEACHER = 'wali-kelas';
    case CLASS_OFFICER = 'pengurus-kelas';
    case DUTY_TEACHER = 'guru-piket';
    case COUNSELING_TEACHER = 'guru-bk';
    case ADMINISTRATION = 'tata-usaha';

    public static function forAccount(Akun $account): ?self
    {
        $role = $account->role;
        if (! $role instanceof Role) {
            return null;
        }

        return self::tryFrom(Str::slug($role->nama_role));
    }
}
