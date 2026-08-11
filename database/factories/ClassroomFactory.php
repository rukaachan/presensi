<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Department;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Classroom> */
class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    public function definition(): array
    {
        return [
            'homeroom_teacher_id' => Teacher::factory(),
            'department_id' => Department::factory(),
            'name' => fake()->unique()->words(2, true),
            'grade_level' => 'X',
            'status' => 'active',
            'created_by_label' => 'Factory',
        ];
    }
}
