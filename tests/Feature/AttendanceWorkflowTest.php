<?php

namespace Tests\Feature;

use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\Student;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CanonicalDatabase;
use Tests\TestCase;

class AttendanceWorkflowTest extends TestCase
{
    use CanonicalDatabase;

    private string $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    public function test_student_capture_writes_only_the_canonical_record_and_private_evidence(): void
    {
        $this->seedCanonicalDatabase();
        Storage::fake('local');
        $account = $this->account('student.demo');
        $student = $this->studentFor($account);
        AttendanceRecord::query()->where('student_id', $student->getKey())->delete();
        AttendanceEvent::query()->where('student_id', $student->getKey())->delete();

        $response = $this->actingAs($account)->post(route('student.attendance.store'), ['image' => $this->png]);

        $response->assertRedirect(route('student.dashboard'));
        $record = AttendanceRecord::query()->where('student_id', $student->getKey())->firstOrFail();
        $this->assertSame('student', $record->source);
        $this->assertNotNull($record->evidence_path);
        Storage::disk('local')->assertExists($record->evidence_path);
        $this->assertFalse(Schema::hasTable('presensi_siswa'));
        $this->assertFalse(Schema::hasTable('validasi'));
    }

    public function test_invalid_capture_leaves_no_record_or_evidence(): void
    {
        $this->seedCanonicalDatabase();
        Storage::fake('local');
        $account = $this->account('student.demo');
        $student = $this->studentFor($account);
        AttendanceRecord::query()->where('student_id', $student->getKey())->delete();

        $this->actingAs($account)->post(route('student.attendance.store'), ['image' => 'not-an-image'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, AttendanceRecord::query()->where('student_id', $student->getKey())->count());
        Storage::disk('local')->assertDirectoryEmpty('attendance/evidence');
    }

    public function test_class_officer_cannot_submit_for_a_different_class(): void
    {
        $this->seedCanonicalDatabase();
        $officer = $this->account('officer.demo');
        $officerStudent = $this->studentFor($officer);
        $target = Student::query()->where('classroom_id', '!=', $officerStudent->classroom_id)->firstOrFail();
        AttendanceRecord::query()->where('student_id', $target->getKey())->delete();

        $this->actingAs($officer)->post(route('class-officer.attendance.store'), ['student_id' => $target->getKey(), 'image' => $this->png])
            ->assertForbidden();
        $this->assertSame(0, AttendanceRecord::query()->where('student_id', $target->getKey())->whereDate('attendance_date', now()->toDateString())->count());
    }

    public function test_duty_teacher_can_correct_a_canonical_record(): void
    {
        $this->seedCanonicalDatabase();
        $record = AttendanceRecord::query()->where('state', 'confirmed')->firstOrFail();

        $this->actingAs($this->account('duty.demo'))->put(route('duty-teacher.attendance.update', ['id' => $record->getKey()]), [
            'state' => 'excused',
            'reason' => 'Dokumen pendukung diterima.',
        ])->assertRedirect(route('duty-teacher.attendance.index'));

        $this->assertDatabaseHas('attendance_records', ['id' => $record->getKey(), 'state' => 'excused']);
        $this->assertDatabaseHas('audit_events', ['action' => 'attendance.corrected']);
    }
}
