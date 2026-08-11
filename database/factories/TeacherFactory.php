<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Teacher> */
class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->name(),
            'photo_path' => 'teacher.jpg',
            'created_by_label' => 'Factory',
        ];
    }
}
