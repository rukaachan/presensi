<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $account_id
 * @property string $name
 * @property string $photo_path
 * @property-read Account|null $account
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Classroom> $homeroomClassrooms
 */
class Teacher extends Model
{
    use HasFactory;

    protected $table = 'teachers';

    protected $fillable = ['account_id', 'name', 'photo_path', 'created_by_label'];

    public $timestamps = false;

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function homeroomClassrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'homeroom_teacher_id');
    }

    public function dutyTeacher(): HasOne
    {
        return $this->hasOne(DutyTeacher::class, 'teacher_id');
    }

    public function counselingTeacher(): HasOne
    {
        return $this->hasOne(CounselingTeacher::class, 'teacher_id');
    }
}
