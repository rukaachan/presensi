<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $student_id
 * @property string $position
 * @property-read Student|null $student
 */
class ClassOfficer extends Model
{
    use HasFactory;

    protected $table = 'class_officers';

    protected $fillable = ['student_id', 'position', 'created_by_label'];

    public $timestamps = false;

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function suggestedEvents(): HasManyThrough
    {
        return $this->hasManyThrough(
            AttendanceEvent::class,
            Student::class,
            'id',
            'student_id',
            'student_id',
            'id',
        );
    }
}
