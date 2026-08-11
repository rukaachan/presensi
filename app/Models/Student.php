<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $account_id
 * @property int $classroom_id
 * @property int $student_number
 * @property string $name
 * @property string $phone
 * @property string $gender
 * @property string $photo_path
 * @property int $admission_year
 * @property string $status
 * @property string|null $position
 * @property-read Account|null $account
 * @property-read Classroom|null $classroom
 */
class Student extends Model
{
    use HasFactory;

    protected $table = 'students';

    protected $fillable = [
        'account_id',
        'classroom_id',
        'student_number',
        'name',
        'phone',
        'gender',
        'photo_path',
        'admission_year',
        'status',
        'position',
        'created_by_label',
    ];

    public $timestamps = false;

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'student_id');
    }

    public function attendanceEvents(): HasMany
    {
        return $this->hasMany(AttendanceEvent::class, 'student_id');
    }

    public function classOfficers(): HasMany
    {
        return $this->hasMany(ClassOfficer::class, 'student_id');
    }

    public function scopeJoinClassroom(Builder $query): Builder
    {
        return $query->join('classrooms', 'students.classroom_id', '=', 'classrooms.id');
    }

    public function scopeJoinClassroomWithHomeroomTeacher(Builder $query): Builder
    {
        return $query
            ->join('classrooms', 'students.classroom_id', '=', 'classrooms.id')
            ->join('teachers', 'teachers.id', '=', 'classrooms.homeroom_teacher_id');
    }
}
