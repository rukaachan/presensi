<?php

namespace Tests\Feature;

use App\Domain\Attendance\AttendanceState;
use App\Domain\Attendance\LeaveRequestState;
use App\Models\Akun;
use App\Models\AttendanceRecord;
use App\Models\AuditEvent;
use App\Models\Siswa;
use App\Services\AttendanceEvidenceStorage;
use App\Services\AttendanceService;
use App\Services\LeaveRequestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class AttendanceDomainServiceTest extends TestCase
{
    public function test_daily_check_in_is_idempotent_and_uses_required_session(): void
    {
        $this->seedDatabase();
        $account = $this->account('siswa.demo');
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
        $this->seedDatabase();
        $officer = $this->account('pengurus.demo');
        $officerStudent = $this->studentFor($officer);
        $target = Siswa::query()
            ->where('id_kelas', $officerStudent->id_kelas)
            ->where('id_siswa', '!=', $officerStudent->getKey())
            ->firstOrFail();

        $event = app(AttendanceService::class)->recordOptionalEvent(
            $officer,
            $target,
            'break_1',
            ['notes' => 'Terlihat hadir di sesi istirahat.'],
        );

        $this->assertSame(AttendanceState::SUBMITTED, $event->state);
        $this->assertNotNull($event->observed_at);
        $this->assertSame('break_1', $event->session()->value('code'));
    }

    public function test_class_officer_suggestion_is_idempotent_and_keeps_the_latest_proposal(): void
    {
        $this->seedDatabase();
        $officer = $this->account('pengurus.demo');
        $officerStudent = $this->studentFor($officer);
        $target = Siswa::query()
            ->where('id_kelas', $officerStudent->id_kelas)
            ->where('id_siswa', '!=', $officerStudent->getKey())
            ->firstOrFail();

        $service = app(AttendanceService::class);
        $service->suggestOptionalEvent($officer, $target, 'break_1', 'izin');
        $event = $service->suggestOptionalEvent($officer, $target, 'break_1', 'alpha');

        $this->assertSame('alpha', $event->proposed_status);
        $this->assertSame(1, DB::table('attendance_events')->count());
        $this->assertDatabaseHas('audit_events', ['action' => 'attendance.event_suggested']);
    }

    public function test_student_policy_cannot_view_another_student_record(): void
    {
        $this->seedDatabase();
        $account = $this->account('siswa.demo');
        $record = app(AttendanceService::class)->recordDailyCheckIn($account, $this->studentFor($account));
        $otherStudentAccount = Akun::query()
            ->where('username', '!=', $account->username)
            ->where('id_role', $account->id_role)
            ->firstOrFail();

        $this->assertFalse(Gate::forUser($otherStudentAccount)->allows('view', $record));
    }

    public function test_audit_events_are_append_only(): void
    {
        $this->seedDatabase();
        $account = $this->account('siswa.demo');
        app(AttendanceService::class)->recordDailyCheckIn($account, $this->studentFor($account));
        $audit = AuditEvent::query()->firstOrFail();

        $this->expectException(LogicException::class);
        $audit->update(['action' => 'tampered']);
    }

    public function test_invalid_evidence_is_rejected_before_storage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(\App\Services\AttendanceEvidenceStorage::class)->storeDataUri('data:text/plain;base64,SGVsbG8=');
    }

    public function test_duty_teacher_review_creates_an_audit_event(): void
    {
        $this->seedDatabase();
        $studentAccount = $this->account('siswa.demo');
        $record = app(AttendanceService::class)->recordDailyCheckIn(
            $studentAccount,
            $this->studentFor($studentAccount),
        );

        $reviewed = app(AttendanceService::class)->transitionRecord(
            $this->account('piket.demo'),
            $record,
            AttendanceState::CONFIRMED,
        );

        $this->assertSame(AttendanceState::CONFIRMED, $reviewed->state);
        $this->assertDatabaseHas('audit_events', [
            'action' => 'attendance.state_changed',
            'subject_type' => AttendanceRecord::class,
            'subject_id' => (string) $record->getKey(),
        ]);
    }

    public function test_approved_leave_request_excuses_linked_attendance(): void
    {
        $this->seedDatabase();
        $studentAccount = $this->account('siswa.demo');
        $student = $this->studentFor($studentAccount);
        $record = app(AttendanceService::class)->recordDailyCheckIn($studentAccount, $student);

        $request = app(LeaveRequestService::class)->submit(
            $studentAccount,
            $student,
            'Sakit dan memerlukan istirahat.',
            $record,
        );
        $approved = app(LeaveRequestService::class)->decide(
            $this->account('piket.demo'),
            $request,
            LeaveRequestState::APPROVED,
        );

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
        $this->assertSame(hash('sha256', base64_decode($png, true)), $stored['hash']);
        $this->assertTrue(app(AttendanceEvidenceStorage::class)->delete($stored['path'], $stored['disk']));
        Storage::disk('local')->assertMissing($stored['path']);
    }

    private function seedDatabase(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->assertSame(Command::SUCCESS, Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]));
        DB::table('attendance_events')->delete();
        DB::table('attendance_records')->delete();
        DB::table('audit_events')->delete();
    }

    private function account(string $username): Akun
    {
        return Akun::query()->where('username', $username)->firstOrFail();
    }

    private function studentFor(Akun $account): Siswa
    {
        return Siswa::query()->where('id_akun', $account->getKey())->firstOrFail();
    }
}
