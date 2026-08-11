<?php

namespace App\Http\Controllers;

use App\Authorization\AttendanceScope;
use App\Http\Requests\StoreAttendanceRequest;
use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\Student;
use App\Services\AttendanceEvidenceStorage;
use App\Services\AttendanceFilterService;
use App\Services\AttendanceService;
use App\Services\AttendanceSessionCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Throwable;

class ClassOfficerController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
        private AttendanceEvidenceStorage $evidenceStorage,
        private AttendanceSessionCatalog $sessionCatalog,
        private AttendanceFilterService $attendanceFilter,
        private AttendanceScope $scope,
    ) {}

    public function index()
    {
        $officer = $this->currentStudent();
        $classStudentIds = Student::query()->where('classroom_id', $officer->classroom_id)->pluck('id');
        $stats = AttendanceRecord::query()->whereIn('student_id', $classStudentIds)
            ->selectRaw("SUM(CASE WHEN state = 'confirmed' THEN 1 ELSE 0 END) AS present")
            ->selectRaw("SUM(CASE WHEN state = 'excused' THEN 1 ELSE 0 END) AS excused")
            ->selectRaw("SUM(CASE WHEN state = 'absent' THEN 1 ELSE 0 END) AS absent")
            ->first();

        return view('class-officer.index', [
            'student' => $officer->load('classroom.department'),
            'totalPresent' => (int) ($stats->present ?? 0),
            'totalExcused' => (int) ($stats->excused ?? 0),
            'totalAbsent' => (int) ($stats->absent ?? 0),
        ]);
    }

    public function showProfile(int $id)
    {
        $student = Student::query()->with(['account', 'classroom.department', 'classOfficers'])->findOrFail($id);
        abort_unless($this->scope->canViewStudent(Auth::user(), $student), 403);

        return view('class-officer.profile', compact('student'));
    }

    public function showHistory(Request $request)
    {
        $officer = $this->currentStudent();
        $attendanceRecords = $this->attendanceFilter->filter(
            $request,
            $this->attendanceFilter->buildBaseQuery()->whereHas('student', static fn ($student) => $student->where('classroom_id', $officer->classroom_id)),
        );

        return view('class-officer.history', compact('officer', 'attendanceRecords'));
    }

    public function openCamera()
    {
        $officer = $this->currentStudent();

        return view('class-officer.attendance', [
            'student' => $officer,
            'students' => Student::query()->where('classroom_id', $officer->classroom_id)->where('status', 'active')->orderBy('name')->get(),
            'requiredSession' => $this->sessionCatalog->required(),
        ]);
    }

    public function store(StoreAttendanceRequest $request)
    {
        $officer = $this->currentStudent();
        $student = Student::query()->findOrFail((int) ($request->input('student_id') ?: $officer->getKey()));
        abort_unless($this->scope->canSubmitFor(Auth::user(), $student), 403);
        $stored = null;

        try {
            $stored = $this->evidenceStorage->storeDataUri($request->string('image')->toString());
            $record = $this->attendanceService->recordDailyCheckIn(Auth::user(), $student, $stored);
        } catch (Throwable $exception) {
            if ($stored !== null) {
                $this->evidenceStorage->delete($stored['path'], $stored['disk']);
            }
            report($exception);

            return back()->with('error', __('attendance.capture_failed'));
        }

        return redirect()->route('class-officer.dashboard')->with('success', __('attendance.recorded', ['date' => Carbon::parse((string) $record->getAttribute('attendance_date'))->format('d/m/Y')]));
    }

    public function checkCapture(Request $request)
    {
        $officer = $this->currentStudent();
        $student = Student::query()->findOrFail((int) ($request->input('student_id') ?: $officer->getKey()));
        abort_unless($this->scope->canSubmitFor(Auth::user(), $student), 403);
        $session = $this->sessionCatalog->required();
        $date = $request->input('date', now((string) config('attendance.timezone'))->toDateString());

        return response()->json(['exists' => $session !== null && AttendanceRecord::query()
            ->where('student_id', $student->getKey())
            ->where('attendance_session_id', $session->getKey())
            ->whereDate('attendance_date', $date)->exists()]);
    }

    public function showClassroom(Request $request)
    {
        $officer = $this->currentStudent();
        $events = AttendanceEvent::query()->with(['student', 'session'])
            ->whereHas('student', static fn ($student) => $student->where('classroom_id', $officer->classroom_id))
            ->when($request->filled('date'), static fn ($query) => $query->whereDate('event_date', $request->input('date')))
            ->latest('event_date')->latest('id')->paginate(25)->withQueryString();

        return view('class-officer.events', [
            'events' => $events,
            'students' => Student::query()->where('classroom_id', $officer->classroom_id)->orderBy('name')->get(),
            'sessions' => $this->sessionCatalog->validationSessions(),
        ]);
    }

    public function suggestAttendanceEvent(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'session_code' => ['required', Rule::in($this->sessionCatalog->validationCodes())],
            'proposed_status' => ['required', Rule::in(['confirmed', 'excused', 'absent', 'checked_out'])],
            'event_date' => ['nullable', 'date'],
        ]);
        $student = Student::query()->findOrFail($data['student_id']);
        abort_unless($this->scope->canObserve(Auth::user(), $student), 403);
        $event = $this->attendanceService->suggestOptionalEvent(Auth::user(), $student, $data['session_code'], $data['proposed_status'], $data);

        $sessionLabel = (string) $event->session()->value('label');

        return back()->with('success', __('attendance.event_suggested', ['session' => __($sessionLabel)]));
    }

    public function exportAttendance(Request $request)
    {
        $officer = $this->currentStudent();
        $attendanceRecords = $this->attendanceFilter->filter($request, $this->attendanceFilter->buildBaseQuery()->whereHas('student', static fn ($student) => $student->where('classroom_id', $officer->classroom_id)), false);

        return Pdf::loadView('class-officer.attendance-report', compact('officer', 'attendanceRecords'))->download('class-attendance.pdf');
    }

    public function exportClassroom(Request $request)
    {
        return $this->exportAttendance($request);
    }

    private function currentStudent(): Student
    {
        return Student::query()->where('account_id', Auth::user()->getKey())->firstOrFail();
    }
}
