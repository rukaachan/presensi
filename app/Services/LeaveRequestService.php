<?php

namespace App\Services;

use App\Authorization\AttendanceScope;
use App\Authorization\RoleCode;
use App\Domain\Attendance\AttendanceState;
use App\Domain\Attendance\LeaveRequestState;
use App\Models\Account;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\Student;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class LeaveRequestService
{
    public function __construct(
        private AttendanceScope $scope,
        private AuditEventService $auditEvents,
        private DatabaseManager $database,
    ) {}

    public function submit(
        Account $actor,
        Student $student,
        string $reason,
        ?AttendanceRecord $attendanceRecord = null,
        ?array $attachment = null,
    ): LeaveRequest {
        if (RoleCode::forAccount($actor) !== RoleCode::STUDENT
            || (int) $student->account_id !== (int) $actor->getKey()) {
            throw new AuthorizationException(__('leave.errors.submit_forbidden'));
        }

        if (blank($reason)) {
            throw new InvalidArgumentException(__('leave.errors.reason_required'));
        }

        return $this->database->transaction(function () use ($actor, $student, $reason, $attendanceRecord, $attachment): LeaveRequest {
            $request = LeaveRequest::query()->create([
                'student_id' => $student->getKey(),
                'attendance_record_id' => $attendanceRecord?->getKey(),
                'state' => LeaveRequestState::SUBMITTED,
                'reason' => $reason,
                'attachment_disk' => $attachment['disk'] ?? null,
                'attachment_path' => $attachment['path'] ?? null,
                'submitted_by' => $actor->getKey(),
            ]);

            $this->auditEvents->record(
                'leave.submitted',
                $request,
                $actor,
                after: [
                    'state' => LeaveRequestState::SUBMITTED->value,
                    'student_id' => $student->getKey(),
                ],
            );

            return $request;
        });
    }

    public function decide(
        Account $actor,
        LeaveRequest $request,
        LeaveRequestState $targetState,
        ?string $decisionNote = null,
    ): LeaveRequest {
        if (! in_array($targetState, [LeaveRequestState::APPROVED, LeaveRequestState::REJECTED], true)) {
            throw new InvalidArgumentException(__('leave.errors.decision_invalid'));
        }

        $student = $request->relationLoaded('student')
            ? $request->student
            : $request->student()->first();
        if (! $student instanceof Student || ! $this->canReview($actor, $student)) {
            throw new AuthorizationException(__('leave.errors.review_forbidden'));
        }

        if ($targetState === LeaveRequestState::REJECTED && blank($decisionNote)) {
            throw new InvalidArgumentException(__('leave.errors.rejection_note_required'));
        }

        $stateAttribute = $request->getAttribute('state');
        $currentState = $stateAttribute instanceof LeaveRequestState
            ? $stateAttribute
            : LeaveRequestState::from((string) $stateAttribute);
        if ($currentState !== LeaveRequestState::SUBMITTED) {
            throw new InvalidArgumentException(__('leave.errors.not_submitted'));
        }

        return $this->database->transaction(function () use ($actor, $request, $targetState, $decisionNote): LeaveRequest {
            $request->update([
                'state' => $targetState,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => Carbon::now((string) config('attendance.timezone', 'Asia/Jakarta')),
                'decision_note' => $decisionNote,
            ]);

            $this->auditEvents->record(
                'leave.decided',
                $request,
                $actor,
                before: ['state' => LeaveRequestState::SUBMITTED->value],
                after: ['state' => $targetState->value],
                metadata: $decisionNote === null ? null : ['decision_note' => $decisionNote],
            );

            if ($targetState === LeaveRequestState::APPROVED && $request->attendance_record_id !== null) {
                $record = AttendanceRecord::query()->find($request->attendance_record_id);
                $recordState = $record?->getAttribute('state');
                if ($record !== null && $recordState !== AttendanceState::EXCUSED) {
                    $before = $recordState instanceof AttendanceState
                        ? $recordState->value
                        : (string) $recordState;
                    $record->update([
                        'state' => AttendanceState::EXCUSED,
                        'updated_by' => $actor->getKey(),
                    ]);
                    $this->auditEvents->record(
                        'attendance.excused',
                        $record,
                        $actor,
                        before: ['state' => $before],
                        after: ['state' => AttendanceState::EXCUSED->value],
                        metadata: ['leave_request_id' => $request->getKey()],
                    );
                }
            }

            return $request->refresh();
        });
    }

    private function canReview(Account $actor, Student $student): bool
    {
        $role = RoleCode::forAccount($actor);

        return in_array($role, [
            RoleCode::HOMEROOM_TEACHER,
            RoleCode::DUTY_TEACHER,
            RoleCode::ADMINISTRATION,
        ], true) && $this->scope->canViewStudent($actor, $student);
    }
}
