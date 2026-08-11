<?php

namespace App\Services;

use App\Authorization\AttendanceScope;
use App\Authorization\RoleCode;
use App\Domain\Attendance\AttendanceState;
use App\Models\Account;
use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\Student;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use LogicException;

class AttendanceService
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'submitted' => ['confirmed', 'needs_review', 'rejected', 'excused', 'absent'],
        'needs_review' => ['confirmed', 'rejected', 'excused', 'absent'],
        'rejected' => ['submitted'],
        'confirmed' => ['needs_review'],
        'excused' => ['needs_review'],
        'absent' => ['needs_review'],
    ];

    public function __construct(
        private AttendanceSessionCatalog $sessionCatalog,
        private AttendanceScope $scope,
        private AuditEventService $auditEvents,
        private DatabaseManager $database,
    ) {}

    public function recordDailyCheckIn(Account $actor, Student $student, array $attributes = []): AttendanceRecord
    {
        if (! $this->scope->canSubmitFor($actor, $student)) {
            throw new AuthorizationException(__('attendance.errors.unauthorized_submit'));
        }

        $session = $this->sessionCatalog->required();
        if ($session === null) {
            throw new LogicException(__('attendance.errors.required_session_missing'));
        }

        $now = CarbonImmutable::now($this->timezone());
        $date = $this->dateValue($attributes['attendance_date'] ?? $now->toDateString());
        $capturedAt = $this->timestampValue($attributes['captured_at'] ?? $now);
        $idempotencyKey = (string) ($attributes['idempotency_key']
            ?? hash('sha256', implode('|', [$student->getKey(), $session->getKey(), $date])));

        return $this->database->transaction(function () use ($actor, $student, $session, $date, $capturedAt, $idempotencyKey, $attributes): AttendanceRecord {
            $record = AttendanceRecord::query()->where('idempotency_key', $idempotencyKey)->first()
                ?? AttendanceRecord::query()
                    ->where('student_id', $student->getKey())
                    ->where('attendance_session_id', $session->getKey())
                    ->whereDate('attendance_date', $date)
                    ->first();

            if ($record === null) {
                try {
                    $record = AttendanceRecord::query()->create([
                        'student_id' => $student->getKey(),
                        'attendance_session_id' => $session->getKey(),
                        'attendance_date' => $date,
                        'state' => AttendanceState::SUBMITTED,
                        'late' => (bool) ($attributes['late'] ?? false),
                        'captured_at' => $capturedAt,
                        'evidence_disk' => $attributes['evidence_disk'] ?? $attributes['disk'] ?? null,
                        'evidence_path' => $attributes['evidence_path'] ?? $attributes['path'] ?? null,
                        'evidence_hash' => $attributes['evidence_hash'] ?? $attributes['hash'] ?? null,
                        'evidence_mime' => $attributes['evidence_mime'] ?? $attributes['mime'] ?? null,
                        'evidence_bytes' => $attributes['evidence_bytes'] ?? $attributes['bytes'] ?? null,
                        'notes' => $attributes['notes'] ?? null,
                        'source' => $attributes['source'] ?? $this->sourceFor($actor),
                        'created_by' => $actor->getKey(),
                        'updated_by' => $actor->getKey(),
                        'idempotency_key' => $idempotencyKey,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    $record = AttendanceRecord::query()
                        ->where('student_id', $student->getKey())
                        ->where('attendance_session_id', $session->getKey())
                        ->whereDate('attendance_date', $date)
                        ->firstOrFail();
                }
            }

            if ($record->wasRecentlyCreated) {
                $this->auditEvents->record(
                    'attendance.submitted',
                    $record,
                    $actor,
                    after: ['state' => AttendanceState::SUBMITTED->value, 'attendance_date' => $date],
                    metadata: ['source' => $record->source],
                );
            }

            return $record;
        });
    }

    public function recordOptionalEvent(Account $actor, Student $student, string $sessionCode, array $attributes = []): AttendanceEvent
    {
        if (! $this->scope->canObserve($actor, $student)) {
            throw new AuthorizationException(__('attendance.errors.unauthorized_observe'));
        }

        $session = $this->sessionCatalog->active()->firstWhere('code', $sessionCode);
        if ($session === null || $session->required) {
            throw new InvalidArgumentException(__('attendance.errors.optional_session_inactive'));
        }

        $now = CarbonImmutable::now($this->timezone());
        $date = $this->dateValue($attributes['event_date'] ?? $now->toDateString());
        $observedAt = $this->timestampValue($attributes['observed_at'] ?? $now);
        $idempotencyKey = (string) ($attributes['idempotency_key']
            ?? hash('sha256', implode('|', [$student->getKey(), $session->getKey(), $date])));

        return $this->database->transaction(function () use ($actor, $student, $session, $date, $observedAt, $idempotencyKey, $attributes): AttendanceEvent {
            $event = AttendanceEvent::query()->where('idempotency_key', $idempotencyKey)->first()
                ?? AttendanceEvent::query()
                    ->where('student_id', $student->getKey())
                    ->where('attendance_session_id', $session->getKey())
                    ->whereDate('event_date', $date)
                    ->first();

            if ($event === null) {
                try {
                    $event = AttendanceEvent::query()->create([
                        'student_id' => $student->getKey(),
                        'attendance_session_id' => $session->getKey(),
                        'event_date' => $date,
                        'state' => AttendanceState::SUBMITTED,
                        'proposed_status' => $attributes['proposed_status'] ?? null,
                        'observed_at' => $observedAt,
                        'notes' => $attributes['notes'] ?? null,
                        'source' => $attributes['source'] ?? $this->sourceFor($actor),
                        'observed_by' => $actor->getKey(),
                        'idempotency_key' => $idempotencyKey,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    $event = AttendanceEvent::query()
                        ->where('student_id', $student->getKey())
                        ->where('attendance_session_id', $session->getKey())
                        ->whereDate('event_date', $date)
                        ->firstOrFail();
                }
            }

            if ($event->wasRecentlyCreated) {
                $this->auditEvents->record(
                    'attendance.event_submitted',
                    $event,
                    $actor,
                    after: [
                        'state' => AttendanceState::SUBMITTED->value,
                        'event_date' => $date,
                        'session_code' => $session->code,
                    ],
                );
            }

            return $event;
        });
    }

    public function suggestOptionalEvent(Account $actor, Student $student, string $sessionCode, string $proposedStatus, array $attributes = []): AttendanceEvent
    {
        if (! in_array($proposedStatus, ['confirmed', 'excused', 'absent', 'checked_out'], true)) {
            throw new InvalidArgumentException(__('attendance.errors.invalid_suggested_status'));
        }

        $event = $this->recordOptionalEvent(
            $actor,
            $student,
            $sessionCode,
            [...$attributes, 'proposed_status' => $proposedStatus],
        );
        if ($event->getAttribute('proposed_status') !== $proposedStatus) {
            $event->update(['proposed_status' => $proposedStatus]);
            $this->auditEvents->record(
                'attendance.event_suggested',
                $event,
                $actor,
                after: ['proposed_status' => $proposedStatus],
            );
        }

        return $event->refresh();
    }

    public function transitionRecord(Account $actor, AttendanceRecord $record, AttendanceState $targetState, ?string $reason = null): AttendanceRecord
    {
        if (! $this->scope->canReview($actor, $record)) {
            throw new AuthorizationException(__('attendance.errors.unauthorized_record_review'));
        }

        $currentState = $this->stateOf($record->getAttribute('state'));
        $this->assertTransition($currentState, $targetState, $reason);

        return $this->database->transaction(function () use ($actor, $record, $currentState, $targetState, $reason): AttendanceRecord {
            $record->update([
                'state' => $targetState,
                'notes' => $reason ?: $record->notes,
                'updated_by' => $actor->getKey(),
            ]);
            $this->auditEvents->record(
                'attendance.state_changed',
                $record,
                $actor,
                before: ['state' => $currentState->value],
                after: ['state' => $targetState->value],
                metadata: $reason === null ? null : ['reason' => $reason],
            );

            return $record->refresh();
        });
    }

    public function transitionEvent(Account $actor, AttendanceEvent $event, AttendanceState $targetState, ?string $reason = null): AttendanceEvent
    {
        if (! $this->scope->canReviewEvent($actor, $event)) {
            throw new AuthorizationException(__('attendance.errors.unauthorized_event_review'));
        }

        $currentState = $this->stateOf($event->getAttribute('state'));
        $this->assertTransition($currentState, $targetState, $reason);

        return $this->database->transaction(function () use ($actor, $event, $currentState, $targetState, $reason): AttendanceEvent {
            $event->update([
                'state' => $targetState,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now($this->timezone()),
                'notes' => $reason ?: $event->notes,
            ]);
            $this->auditEvents->record(
                'attendance.event_state_changed',
                $event,
                $actor,
                before: ['state' => $currentState->value],
                after: ['state' => $targetState->value],
                metadata: $reason === null ? null : ['reason' => $reason],
            );

            return $event->refresh();
        });
    }

    public function correctRecord(Account $actor, AttendanceRecord $record, AttendanceState $targetState, string $reason, array $attributes = []): AttendanceRecord
    {
        if (! $this->scope->canReview($actor, $record)) {
            throw new AuthorizationException(__('attendance.errors.unauthorized_record_correction'));
        }
        if (blank($reason)) {
            throw new InvalidArgumentException(__('attendance.errors.correction_reason_required'));
        }

        $currentState = $this->stateOf($record->getAttribute('state'));

        return $this->database->transaction(function () use ($actor, $record, $currentState, $targetState, $reason, $attributes): AttendanceRecord {
            $record->update(array_filter([
                'state' => $targetState,
                'notes' => $reason,
                'updated_by' => $actor->getKey(),
                'evidence_disk' => $attributes['evidence_disk'] ?? $attributes['disk'] ?? null,
                'evidence_path' => $attributes['evidence_path'] ?? $attributes['path'] ?? null,
                'evidence_hash' => $attributes['evidence_hash'] ?? $attributes['hash'] ?? null,
                'evidence_mime' => $attributes['evidence_mime'] ?? $attributes['mime'] ?? null,
                'evidence_bytes' => $attributes['evidence_bytes'] ?? $attributes['bytes'] ?? null,
            ], static fn (mixed $value): bool => $value !== null));
            $this->auditEvents->record(
                'attendance.corrected',
                $record,
                $actor,
                before: ['state' => $currentState->value],
                after: ['state' => $targetState->value],
                metadata: ['reason' => $reason],
            );

            return $record->refresh();
        });
    }

    private function assertTransition(AttendanceState $current, AttendanceState $target, ?string $reason): void
    {
        if (! in_array($target->value, self::TRANSITIONS[$current->value], true)) {
            throw new InvalidArgumentException(__('attendance.errors.invalid_transition', [
                'current' => __('attendance.'.$current->value),
                'target' => __('attendance.'.$target->value),
            ]));
        }
        if ($target === AttendanceState::REJECTED && blank($reason)) {
            throw new InvalidArgumentException(__('attendance.errors.rejection_reason_required'));
        }
    }

    private function stateOf(mixed $state): AttendanceState
    {
        return $state instanceof AttendanceState ? $state : AttendanceState::from((string) $state);
    }

    private function dateValue(mixed $value): string
    {
        return Carbon::parse((string) $value, $this->timezone())->toDateString();
    }

    private function timestampValue(mixed $value): Carbon
    {
        return $value instanceof DateTimeInterface
            ? Carbon::instance($value)
            : Carbon::parse((string) $value, $this->timezone());
    }

    private function sourceFor(Account $actor): string
    {
        return match (RoleCode::forAccount($actor)) {
            RoleCode::STUDENT => 'student',
            RoleCode::CLASS_OFFICER => 'class_officer',
            RoleCode::DUTY_TEACHER => 'duty_teacher',
            RoleCode::HOMEROOM_TEACHER => 'homeroom_teacher',
            RoleCode::COUNSELING_TEACHER => 'counseling_teacher',
            RoleCode::ADMINISTRATION => 'administrator',
            default => 'authorized',
        };
    }

    private function timezone(): string
    {
        return (string) config('attendance.timezone', 'Asia/Jakarta');
    }
}
