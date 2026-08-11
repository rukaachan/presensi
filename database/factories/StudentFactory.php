<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Student> */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'classroom_id' => Classroom::factory(),
            'student_number' => fake()->unique()->numerify('########'),
            'name' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'gender' => fake()->randomElement(['male', 'female']),
            'photo_path' => 'student.jpg',
            'admission_year' => (int) date('Y'),
            'status' => 'active',
            'position' => 'student',
            'created_by_label' => 'Factory',
        ];
    }
}
