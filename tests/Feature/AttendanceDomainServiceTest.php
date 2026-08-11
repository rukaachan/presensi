<?php

namespace Tests\Feature;

use App\Domain\Attendance\AttendanceState;
use App\Domain\Attendance\LeaveRequestState;
use App\Models\Account;
use App\Models\AttendanceRecord;
use App\Models\AuditEvent;
use App\Models\Student;
use App\Services\AttendanceEvidenceStorage;
use App\Services\AttendanceService;
use App\Services\LeaveRequestService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use LogicException;
use Tests\Support\CanonicalDatabase;
use Tests\TestCase;

class AttendanceDomainServiceTest extends TestCase
{
    use CanonicalDatabase;

    public function test_daily_check_in_is_idempotent_and_uses_required_session(): void
    {
        $this->freshOperationalData();
        $account = $this->account('student.demo');
        $student = $this->studentFor($account);
        $service = app(AttendanceService::class);

        $first = $service->recordDailyCheckIn($account, $student);
        $second = $service->recordDailyCheckIn($account, $student);

        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame(AttendanceState::SUBMITTED, $first->state);
        $this->assertSame('daily_check_in', $first->session()->value('code'));
        $this->assertTrue(Gate::forUser($account)->allows('view', $first));
        $this->assertSame(1, AttendanceRecord::query()->count());
    }

    public function test_class_officer_can_record_optional_event_for_their_class(): void
    {
        $this->freshOperationalData();
        $officer = $this->account('officer.demo');
        $officerStudent = $this->studentFor($officer);
        $target = Student::query()->where('classroom_id', $officerStudent->classroom_id)->where('id', '!=', $officerStudent->getKey())->firstOrFail();

        $event = app(AttendanceService::class)->recordOptionalEvent($officer, $target, 'break_1');

        $this->assertSame(AttendanceState::SUBMITTED, $event->state);
        $this->assertNotNull($event->observed_at);
        $this->assertSame('break_1', $event->session()->value('code'));
    }

    public function test_class_officer_suggestion_is_idempotent_and_keeps_the_latest_proposal(): void
    {
        $this->freshOperationalData();
        $officer = $this->account('officer.demo');
        $officerStudent = $this->studentFor($officer);
        $target = Student::query()->where('classroom_id', $officerStudent->classroom_id)->where('id', '!=', $officerStudent->getKey())->firstOrFail();
        $service = app(AttendanceService::class);

        $service->suggestOptionalEvent($officer, $target, 'break_1', 'excused');
        $event = $service->suggestOptionalEvent($officer, $target, 'break_1', 'absent');

        $this->assertSame('absent', $event->proposed_status);
        $this->assertSame(1, DB::table('attendance_events')->count());
        $this->assertDatabaseHas('audit_events', ['action' => 'attendance.event_suggested']);
    }

    public function test_student_policy_cannot_view_another_student_record(): void
    {
        $this->freshOperationalData();
        $account = $this->account('student.demo');
        $record = app(AttendanceService::class)->recordDailyCheckIn($account, $this->studentFor($account));
        $otherStudentAccount = Account::query()->where('username', '!=', $account->username)->where('role_id', $account->role_id)->firstOrFail();

        $this->assertFalse(Gate::forUser($otherStudentAccount)->allows('view', $record));
    }

    public function test_audit_events_are_append_only(): void
    {
        $this->freshOperationalData();
        $account = $this->account('student.demo');
        app(AttendanceService::class)->recordDailyCheckIn($account, $this->studentFor($account));
        $audit = AuditEvent::query()->firstOrFail();

        $this->expectException(LogicException::class);
        $audit->update(['action' => 'tampered']);
    }

    public function test_invalid_evidence_is_rejected_before_storage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(AttendanceEvidenceStorage::class)->storeDataUri('data:text/plain;base64,SGVsbG8=');
    }

    public function test_duty_teacher_review_creates_an_audit_event(): void
    {
        $this->freshOperationalData();
        $studentAccount = $this->account('student.demo');
        $record = app(AttendanceService::class)->recordDailyCheckIn($studentAccount, $this->studentFor($studentAccount));
        $reviewed = app(AttendanceService::class)->transitionRecord($this->account('duty.demo'), $record, AttendanceState::CONFIRMED);

        $this->assertSame(AttendanceState::CONFIRMED, $reviewed->state);
        $this->assertDatabaseHas('audit_events', ['action' => 'attendance.state_changed']);
    }

    public function test_approved_leave_request_excuses_linked_attendance(): void
    {
        $this->freshOperationalData();
        $studentAccount = $this->account('student.demo');
        $student = $this->studentFor($studentAccount);
        $record = app(AttendanceService::class)->recordDailyCheckIn($studentAccount, $student);
        $request = app(LeaveRequestService::class)->submit($studentAccount, $student, 'Sakit dan memerlukan istirahat.', $record);
        $approved = app(LeaveRequestService::class)->decide($this->account('duty.demo'), $request, LeaveRequestState::APPROVED);

        $this->assertSame(LeaveRequestState::APPROVED, $approved->state);
        $this->assertSame(AttendanceState::EXCUSED, $record->refresh()->state);
    }

    public function test_evidence_storage_keeps_files_private_and_deletable(): void
    {
        Storage::fake('local');
        $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
        $stored = app(AttendanceEvidenceStorage::class)->storeDataUri("data:image/png;base64,{$png}");

        Storage::disk('local')->assertExists($stored['path']);
        $this->assertSame('local', $stored['disk']);
        $this->assertTrue(app(AttendanceEvidenceStorage::class)->delete($stored['path'], $stored['disk']));
        Storage::disk('local')->assertMissing($stored['path']);
    }

    private function freshOperationalData(): void
    {
        $this->seedCanonicalDatabase();
        DB::table('attendance_events')->delete();
        DB::table('attendance_records')->delete();
        DB::table('audit_events')->delete();
    }
}
