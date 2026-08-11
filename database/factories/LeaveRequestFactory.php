<?php

namespace Database\Factories;

use App\Models\LeaveRequest;
use App\Models\Siswa;
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
            'student_id' => Siswa::factory(),
            'attendance_record_id' => null,
            'state' => 'submitted',
            'reason' => fake()->sentence(8),
            'attachment_disk' => null,
            'attachment_path' => null,
            'submitted_by' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'decision_note' => null,
            'legacy_surat_presensi_id' => null,
        ];
    }
}
