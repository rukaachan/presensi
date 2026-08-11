<?php

namespace App\Http\Controllers;

use App\Authorization\AttendanceScope;
use App\Http\Requests\StoreAttendanceRequest;
use App\Models\AttendanceRecord;
use App\Models\Student;
use App\Services\AttendanceEvidenceStorage;
use App\Services\AttendanceFilterService;
use App\Services\AttendanceService;
use App\Services\AttendanceSessionCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class StudentController extends Controller
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
        $student = $this->currentStudent();
        $records = AttendanceRecord::query()->where('student_id', $student->getKey());
        $stats = (clone $records)->selectRaw("SUM(CASE WHEN state = 'confirmed' THEN 1 ELSE 0 END) AS present")
            ->selectRaw("SUM(CASE WHEN state = 'excused' THEN 1 ELSE 0 END) AS excused")
            ->selectRaw("SUM(CASE WHEN state = 'absent' THEN 1 ELSE 0 END) AS absent")
            ->first();

        return view('student.index', [
            'student' => $student->load('classroom.department'),
            'totalPresent' => (int) ($stats->present ?? 0),
            'totalExcused' => (int) ($stats->excused ?? 0),
            'totalAbsent' => (int) ($stats->absent ?? 0),
            'recentRecords' => (clone $records)->with('session')->latest('attendance_date')->limit(10)->get(),
        ]);
    }

    public function showProfile(int $id)
    {
        $student = Student::query()->with(['account', 'classroom.department', 'classOfficers'])->findOrFail($id);
        abort_unless($this->scope->canViewStudent(Auth::user(), $student), 403);

        return view('student.profile', compact('student'));
    }

    public function showHistory(Request $request)
    {
        $student = $this->currentStudent();
        $records = $this->attendanceFilter->filter(
            $request,
            $this->attendanceFilter->buildBaseQuery()->where('student_id', $student->getKey()),
        );

        return view('student.history', ['student' => $student, 'attendanceRecords' => $records]);
    }

    public function openCamera()
    {
        return view('student.attendance', [
            'student' => $this->currentStudent(),
            'requiredSession' => $this->sessionCatalog->required(),
        ]);
    }

    public function store(StoreAttendanceRequest $request)
    {
        $student = $this->currentStudent();
        $stored = null;

        try {
            $stored = $this->evidenceStorage->storeDataUri($request->string('image')->toString());
            $record = $this->attendanceService->recordDailyCheckIn(
                Auth::user(),
                $student,
                $stored + ['notes' => __('attendance.submitted')],
            );
        } catch (Throwable $exception) {
            if ($stored !== null) {
                $this->evidenceStorage->delete($stored['path'], $stored['disk']);
            }

            report($exception);

            return back()->with('error', __('attendance.capture_failed'));
        }

        return redirect()->route('student.dashboard')->with('success', __('attendance.recorded', ['date' => \Illuminate\Support\Carbon::parse((string) $record->getAttribute('attendance_date'))->format('d/m/Y')]));
    }

    public function checkCapture(Request $request)
    {
        $student = $this->currentStudent();
        $date = $request->input('date', now((string) config('attendance.timezone'))->toDateString());
        $session = $this->sessionCatalog->required();
        $exists = $session !== null && AttendanceRecord::query()
            ->where('student_id', $student->getKey())
            ->where('attendance_session_id', $session->getKey())
            ->whereDate('attendance_date', $date)
            ->exists();

        return response()->json(['exists' => $exists]);
    }

    public function exportAttendance(Request $request)
    {
        $student = $this->currentStudent();
        $attendanceRecords = $this->attendanceFilter->filter(
            $request,
            $this->attendanceFilter->buildBaseQuery()->where('student_id', $student->getKey()),
            false,
        );

        return Pdf::loadView('student.attendance-report', compact('student', 'attendanceRecords'))->download('attendance-'.$student->getKey().'.pdf');
    }

    private function currentStudent(): Student
    {
        $account = Auth::user();

        return Student::query()->where('account_id', $account->getKey())->firstOrFail();
    }
}
