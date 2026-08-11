<?php

namespace App\Services;

use App\Authorization\AttendanceScope;
use App\Authorization\RoleCode;
use App\Domain\Attendance\AttendanceState;
use App\Models\Akun;
use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
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
    /**
     * @var array<string, list<string>>
     */
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
        private LegacyAttendanceAdapter $legacyStatuses,
        private DatabaseManager $database,
    ) {}

    public function recordDailyCheckIn(Akun $actor, Siswa $student, array $attributes = []): AttendanceRecord
    {
        if (! $this->scope->canSubmitFor($actor, $student)) {
            throw new AuthorizationException('The actor cannot submit attendance for this student.');
        }

        $session = $this->sessionCatalog->required();
        if ($session === null) {
            throw new LogicException('The required daily attendance session is not configured.');
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
                        'evidence_disk' => $attributes['evidence_disk'] ?? null,
                        'evidence_path' => $attributes['evidence_path'] ?? null,
                        'evidence_hash' => $attributes['evidence_hash'] ?? null,
                        'evidence_mime' => $attributes['evidence_mime'] ?? null,
                        'evidence_bytes' => $attributes['evidence_bytes'] ?? null,
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
                    after: [
                        'state' => AttendanceState::SUBMITTED->value,
                        'attendance_date' => $date,
                    ],
                    metadata: ['source' => $record->source],
                );
            }

            return $record;
        });
    }

    public function recordOptionalEvent(
        Akun $actor,
        Siswa $student,
        string $sessionCode,
        array $attributes = [],
    ): AttendanceEvent {
        if (! $this->scope->canObserve($actor, $student)) {
            throw new AuthorizationException('The actor cannot observe an event for this student.');
        }

        $session = $this->sessionCatalog->active()->firstWhere('code', $sessionCode);
        if ($session === null || $session->required) {
            throw new InvalidArgumentException('The event session is not an active optional session.');
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

    public function suggestOptionalEvent(
        Akun $actor,
        Siswa $student,
        string $sessionCode,
        string $proposedStatus,
        array $attributes = [],
    ): AttendanceEvent {
        if (! in_array($proposedStatus, ['hadir', 'izin', 'alpha', 'pulang'], true)) {
            throw new InvalidArgumentException('The suggested attendance status is invalid.');
        }

        $event = $this->recordOptionalEvent(
            $actor,
            $student,
            $sessionCode,
            [...$attributes, 'proposed_status' => $proposedStatus],
        );
        $currentProposal = $event->getAttribute('proposed_status');
        if ($currentProposal !== $proposedStatus) {
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

    public function transitionRecord(
        Akun $actor,
        AttendanceRecord $record,
        AttendanceState $targetState,
        ?string $reason = null,
    ): AttendanceRecord {
        if (! $this->scope->canReview($actor, $record)) {
            throw new AuthorizationException('The actor cannot review this attendance record.');
        }

        $stateAttribute = $record->getAttribute('state');
        $currentState = $stateAttribute instanceof AttendanceState
            ? $stateAttribute
            : AttendanceState::from((string) $stateAttribute);
        $allowed = self::TRANSITIONS[$currentState->value];
        if (! in_array($targetState->value, $allowed, true)) {
            throw new InvalidArgumentException("Cannot transition attendance from {$currentState->value} to {$targetState->value}.");
        }

        if ($targetState === AttendanceState::REJECTED && blank($reason)) {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

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

    public function transitionEvent(
        Akun $actor,
        AttendanceEvent $event,
        AttendanceState $targetState,
        ?string $reason = null,
    ): AttendanceEvent {
        if (! $this->scope->canReviewEvent($actor, $event)) {
            throw new AuthorizationException('The actor cannot review this attendance event.');
        }

        $stateAttribute = $event->getAttribute('state');
        $currentState = $stateAttribute instanceof AttendanceState
            ? $stateAttribute
            : AttendanceState::from((string) $stateAttribute);
        $allowed = self::TRANSITIONS[$currentState->value];
        if (! in_array($targetState->value, $allowed, true)) {
            throw new InvalidArgumentException("Cannot transition attendance event from {$currentState->value} to {$targetState->value}.");
        }

        if ($targetState === AttendanceState::REJECTED && blank($reason)) {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

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

    public function correctRecord(
        Akun $actor,
        AttendanceRecord $record,
        AttendanceState $targetState,
        string $reason,
        array $attributes = [],
    ): AttendanceRecord {
        if (! $this->scope->canReview($actor, $record)) {
            throw new AuthorizationException('The actor cannot correct this attendance record.');
        }

        if (blank($reason)) {
            throw new InvalidArgumentException('A correction reason is required.');
        }

        $currentAttribute = $record->getAttribute('state');
        $currentState = $currentAttribute instanceof AttendanceState
            ? $currentAttribute
            : AttendanceState::from((string) $currentAttribute);

        return $this->database->transaction(function () use ($actor, $record, $currentState, $targetState, $reason, $attributes): AttendanceRecord {
            $record->update(array_filter([
                'state' => $targetState,
                'notes' => $reason,
                'updated_by' => $actor->getKey(),
                'evidence_disk' => $attributes['evidence_disk'] ?? null,
                'evidence_path' => $attributes['evidence_path'] ?? null,
                'evidence_hash' => $attributes['evidence_hash'] ?? null,
                'evidence_mime' => $attributes['evidence_mime'] ?? null,
                'evidence_bytes' => $attributes['evidence_bytes'] ?? null,
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

    public function synchronizeLegacyRecord(
        Akun $actor,
        PresensiSiswa $legacy,
        string $legacyStatus,
        string $reason,
        array $attributes = [],
    ): AttendanceRecord {
        $student = $legacy->siswa()->first();
        if (! $student instanceof Siswa) {
            throw new InvalidArgumentException('The legacy attendance record has no student.');
        }

        if (! $this->scope->canViewStudent($actor, $student)) {
            throw new AuthorizationException('The actor cannot synchronize this attendance record.');
        }

        $session = $this->sessionCatalog->required();
        if ($session === null) {
            throw new LogicException('The required daily attendance session is not configured.');
        }

        $date = $this->dateValue($legacy->getRawOriginal('tanggal'));
        $targetState = AttendanceState::from($this->legacyStatuses->reviewStateFromAttendanceStatus($legacyStatus));
        $record = AttendanceRecord::query()
            ->where('legacy_presensi_id', $legacy->getKey())
            ->first()
            ?? AttendanceRecord::query()
                ->where('student_id', $student->getKey())
                ->where('attendance_session_id', $session->getKey())
                ->whereDate('attendance_date', $date)
                ->first();

        if ($record === null) {
            $record = $this->database->transaction(function () use ($actor, $legacy, $student, $session, $date, $targetState, $attributes): AttendanceRecord {
                $record = AttendanceRecord::query()->create([
                    'student_id' => $student->getKey(),
                    'attendance_session_id' => $session->getKey(),
                    'attendance_date' => $date,
                    'state' => $targetState,
                    'captured_at' => $this->timestampValue($legacy->getRawOriginal('jam_masuk') === null
                        ? now($this->timezone())
                        : sprintf('%s %s', $date, $legacy->getRawOriginal('jam_masuk'))),
                    'evidence_disk' => $attributes['evidence_disk'] ?? null,
                    'evidence_path' => $attributes['evidence_path'] ?? null,
                    'evidence_hash' => $attributes['evidence_hash'] ?? null,
                    'evidence_mime' => $attributes['evidence_mime'] ?? null,
                    'evidence_bytes' => $attributes['evidence_bytes'] ?? null,
                    'notes' => $legacy->getAttribute('keterangan'),
                    'source' => 'legacy_compatibility',
                    'created_by' => $actor->getKey(),
                    'updated_by' => $actor->getKey(),
                    'idempotency_key' => 'legacy-presensi:'.$legacy->getKey(),
                    'legacy_presensi_id' => $legacy->getKey(),
                ]);

                $this->auditEvents->record(
                    'attendance.legacy_synchronized',
                    $record,
                    $actor,
                    after: ['state' => $targetState->value],
                    metadata: ['legacy_presensi_id' => $legacy->getKey()],
                );

                return $record;
            });
        } else {
            $currentAttribute = $record->getAttribute('state');
            $currentState = $currentAttribute instanceof AttendanceState
                ? $currentAttribute
                : AttendanceState::from((string) $currentAttribute);
            if ($currentState !== $targetState) {
                $record = $this->correctRecord($actor, $record, $targetState, $reason, $attributes);
            }
        }

        return $record->refresh();
    }

    private function dateValue(mixed $value): string
    {
        return Carbon::parse((string) $value, $this->timezone())->toDateString();
    }

    private function timestampValue(mixed $value): Carbon
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        return Carbon::parse((string) $value, $this->timezone());
    }

    private function sourceFor(Akun $actor): string
    {
        return match (RoleCode::forAccount($actor)) {
            RoleCode::STUDENT => 'student',
            RoleCode::CLASS_OFFICER => 'class_officer',
            RoleCode::DUTY_TEACHER => 'duty_teacher',
            RoleCode::HOMEROOM_TEACHER => 'homeroom_teacher',
            RoleCode::COUNSELING_TEACHER => 'counseling_teacher',
            RoleCode::ADMINISTRATION => 'admin',
            default => 'authorized',
        };
    }

    private function timezone(): string
    {
        return (string) config('attendance.timezone', 'Asia/Jakarta');
    }
}
