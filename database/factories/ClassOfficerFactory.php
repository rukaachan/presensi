<?php

namespace Database\Factories;

use App\Models\ClassOfficer;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ClassOfficer> */
class ClassOfficerFactory extends Factory
{
    protected $model = ClassOfficer::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'position' => 'class_officer',
            'created_by_label' => 'Factory',
        ];
    }
}
