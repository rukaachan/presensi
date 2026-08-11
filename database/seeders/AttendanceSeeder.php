<?php

namespace Database\Seeders;

use App\Domain\Attendance\AttendanceState;
use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassOfficer;
use App\Models\Student;
use App\Services\AttendanceSessionCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(AttendanceSessionCatalog $sessionCatalog): void
    {
        $timezone = (string) config('attendance.timezone', 'Asia/Jakarta');
        $requiredSession = $sessionCatalog->required();
        if ($requiredSession === null) {
            return;
        }

        $optionalSessions = $sessionCatalog->active()
            ->filter(static fn (AttendanceSession $session): bool => ! $session->required)
            ->values();
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $students = Student::query()->orderBy('classroom_id')->orderBy('id')->get();
        $officersByClass = ClassOfficer::query()
            ->with('student')
            ->get()
            ->filter(static fn (ClassOfficer $officer): bool => $officer->student !== null)
            ->keyBy(static fn (ClassOfficer $officer): string => (string) $officer->student->classroom_id);

        foreach ($students as $index => $student) {
            for ($daysAgo = 0; $daysAgo < 7; $daysAgo++) {
                $state = $this->stateFor((int) $index, $daysAgo);
                $date = $today->subDays($daysAgo)->toDateString();
                $existing = AttendanceRecord::query()
                    ->where('student_id', $student->getKey())
                    ->where('attendance_session_id', $requiredSession->getKey())
                    ->whereDate('attendance_date', $date)
                    ->first();

                if ($state === null) {
                    $existing?->delete();
                    AttendanceEvent::query()->where('student_id', $student->getKey())
                        ->whereDate('event_date', $date)->delete();

                    continue;
                }

                $record = $existing ?: new AttendanceRecord;
                $record->fill([
                    'student_id' => $student->getKey(),
                    'attendance_session_id' => $requiredSession->getKey(),
                    'attendance_date' => $date,
                    'state' => $state,
                    'late' => false,
                    'captured_at' => CarbonImmutable::parse($date.' 07:00:00', $timezone),
                    'evidence_disk' => null,
                    'evidence_path' => null,
                    'evidence_hash' => null,
                    'evidence_mime' => null,
                    'evidence_bytes' => null,
                    'notes' => $state === AttendanceState::CONFIRMED ? 'Presensi demo' : 'Perlu tindak lanjut',
                    'source' => 'demo',
                    'idempotency_key' => 'demo:attendance:'.$student->getKey().':'.$date,
                ]);
                $record->save();

                if ($state !== AttendanceState::CONFIRMED) {
                    continue;
                }

                $officer = $officersByClass->get((string) $student->classroom_id);
                foreach ($optionalSessions as $session) {
                    $event = AttendanceEvent::query()
                        ->where('student_id', $student->getKey())
                        ->where('attendance_session_id', $session->getKey())
                        ->whereDate('event_date', $date)
                        ->first() ?: new AttendanceEvent;
                    $pending = $daysAgo === 0 && ((int) $index % 5 === 0);
                    $event->fill([
                        'student_id' => $student->getKey(),
                        'attendance_session_id' => $session->getKey(),
                        'event_date' => $date,
                        'state' => $pending ? AttendanceState::NEEDS_REVIEW : AttendanceState::CONFIRMED,
                        'proposed_status' => $pending ? null : 'confirmed',
                        'observed_at' => null,
                        'notes' => null,
                        'source' => 'demo',
                        'observed_by' => $officer?->student?->account_id,
                        'idempotency_key' => 'demo:event:'.$student->getKey().':'.$session->getKey().':'.$date,
                    ]);
                    $event->save();
                }
            }
        }
    }

    private function stateFor(int $studentIndex, int $daysAgo): ?AttendanceState
    {
        if ($daysAgo === 0) {
            return [
                AttendanceState::CONFIRMED,
                AttendanceState::CONFIRMED,
                AttendanceState::NEEDS_REVIEW,
                AttendanceState::ABSENT,
                AttendanceState::CONFIRMED,
                AttendanceState::CONFIRMED,
                AttendanceState::CONFIRMED,
                null,
            ][$studentIndex % 8];
        }

        return [
            AttendanceState::CONFIRMED,
            AttendanceState::CONFIRMED,
            AttendanceState::CONFIRMED,
            AttendanceState::NEEDS_REVIEW,
            AttendanceState::CONFIRMED,
            AttendanceState::ABSENT,
            AttendanceState::CONFIRMED,
        ][($studentIndex + $daysAgo) % 7];
    }
}
