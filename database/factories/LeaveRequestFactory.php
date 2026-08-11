<?php

namespace Database\Factories;

use App\Models\LeaveRequest;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'attendance_record_id' => null,
            'state' => 'submitted',
            'reason' => fake()->sentence(8),
            'attachment_disk' => null,
            'attachment_path' => null,
            'submitted_by' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'decision_note' => null,
            'source_letter_id' => null,
        ];
    }
}
