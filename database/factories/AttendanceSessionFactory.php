<?php

namespace Database\Factories;

use App\Models\AttendanceSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceSession>
 */
class AttendanceSessionFactory extends Factory
{
    protected $model = AttendanceSession::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2),
            'label' => fake()->sentence(3),
            'kind' => 'special',
            'required' => false,
            'active' => true,
            'window_start' => null,
            'window_end' => null,
            'sort_order' => fake()->numberBetween(1, 100),
            'settings' => ['evidence' => 'optional'],
        ];
    }
}
