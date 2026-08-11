<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DutyTeacher extends Model
{
    use HasFactory;

    protected $table = 'duty_teachers';

    protected $fillable = ['teacher_id'];

    public $timestamps = false;

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}
