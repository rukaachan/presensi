<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property int $role_id
 * @property string $username
 * @property-read Role|null $role
 * @property-read Teacher|null $teacher
 * @property-read Student|null $student
 */
class Account extends Authenticatable
{
    use HasFactory;

    protected $table = 'accounts';

    protected $fillable = ['role_id', 'username', 'password'];

    protected $hidden = ['password'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class, 'account_id');
    }

    public function administrationStaff(): HasOne
    {
        return $this->hasOne(AdministrationStaff::class, 'account_id');
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'account_id');
    }
}
