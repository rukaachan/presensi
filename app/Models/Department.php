<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $table = 'departments';

    protected $fillable = ['name', 'created_by_label'];

    public $timestamps = false;

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'department_id');
    }
}
