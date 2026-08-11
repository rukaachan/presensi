<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $homeroom_teacher_id
 * @property int $department_id
 * @property string $name
 * @property string $grade_level
 * @property string $status
 * @property-read Teacher|null $homeroomTeacher
 * @property-read Department|null $department
 */
class Classroom extends Model
{
    use HasFactory;

    protected $table = 'classrooms';

    protected $fillable = ['homeroom_teacher_id', 'department_id', 'name', 'grade_level', 'status', 'created_by_label'];

    public $timestamps = false;

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'classroom_id');
    }
}
