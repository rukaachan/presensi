<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        return [
            'student_id' => Siswa::factory(),
            'attendance_session_id' => AttendanceSession::factory(),
            'attendance_date' => fake()->date(),
            'state' => 'submitted',
            'late' => false,
            'captured_at' => now(),
            'evidence_disk' => null,
            'evidence_path' => null,
            'evidence_hash' => null,
            'evidence_mime' => null,
            'evidence_bytes' => null,
            'notes' => null,
            'source' => 'factory',
            'created_by' => null,
            'updated_by' => null,
            'idempotency_key' => null,
            'legacy_presensi_id' => null,
        ];
    }
}
