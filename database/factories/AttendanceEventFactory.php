<?php

namespace Database\Factories;

use App\Models\AttendanceEvent;
use App\Models\AttendanceSession;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceEvent>
 */
class AttendanceEventFactory extends Factory
{
    protected $model = AttendanceEvent::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'attendance_session_id' => AttendanceSession::factory(),
            'event_date' => fake()->date(),
            'state' => 'submitted',
            'proposed_status' => null,
            'observed_at' => now(),
            'notes' => null,
            'source' => 'factory',
            'observed_by' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'idempotency_key' => null,
            'source_event_id' => null,
            'source_attendance_id' => null,
        ];
    }
}
