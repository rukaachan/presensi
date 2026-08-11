<?php

namespace App\Http\Controllers;

use App\Authorization\AttendanceScope;
use App\Domain\Attendance\AttendanceState;
use App\Models\AttendanceRecord;
use App\Models\ClassOfficer;
use App\Models\Classroom;
use App\Models\Teacher;
use App\Services\AttendanceEvidenceStorage;
use App\Services\AttendanceFilterService;
use App\Services\AttendanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Throwable;

class DutyTeacherController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
        private AttendanceEvidenceStorage $evidenceStorage,
        private AttendanceFilterService $attendanceFilter,
        private AttendanceScope $scope,
    ) {}

    public function index()
    {
        $stats = AttendanceRecord::query()
            ->selectRaw("SUM(CASE WHEN state = 'confirmed' THEN 1 ELSE 0 END) AS totalPresent")
            ->selectRaw("SUM(CASE WHEN state = 'excused' THEN 1 ELSE 0 END) AS totalExcused")
            ->selectRaw("SUM(CASE WHEN state = 'absent' THEN 1 ELSE 0 END) AS totalAbsent")
            ->first();

        return view('duty-teacher.index', [
            'teacher' => Auth::user()->teacher()->first(),
            'totalPresent' => (int) ($stats->totalPresent ?? 0),
            'totalExcused' => (int) ($stats->totalExcused ?? 0),
            'totalAbsent' => (int) ($stats->totalAbsent ?? 0),
        ]);
    }

    public function showProfile(int $id)
    {
        return view('duty-teacher.profile', ['teacher' => Teacher::query()->with('account.role')->findOrFail($id)]);
    }

    public function showClassOfficers(Request $request)
    {
        $officers = ClassOfficer::query()->with('student.classroom.department')
            ->when($request->filled('keyword'), static fn ($query) => $query->whereHas('student', static fn ($student) => $student->where('name', 'like', '%'.request()->string('keyword').'%')))
            ->paginate(25)->withQueryString();

        return view('duty-teacher.class-officers', ['officers' => $officers, 'classrooms' => Classroom::query()->orderBy('name')->get()]);
    }

    public function detailClassOfficer(int $id)
    {
        return view('duty-teacher.class-officer-detail', ['officer' => ClassOfficer::query()->with('student.classroom.department')->findOrFail($id)]);
    }

    public function showAttendance(Request $request)
    {
        $attendanceRecords = $this->attendanceFilter->filter($request, $this->attendanceFilter->buildBaseQuery());

        return view('duty-teacher.attendance', [
            'attendanceRecords' => $attendanceRecords,
            'classrooms' => Classroom::query()->with('department')->orderBy('name')->get(),
        ]);
    }

    public function detailAttendance(int $id)
    {
        return view('duty-teacher.attendance-detail', ['attendanceRecord' => AttendanceRecord::query()->with(['student.classroom.department', 'session'])->findOrFail($id)]);
    }

    public function editAttendance(int $id)
    {
        return view('duty-teacher.edit-attendance', ['attendanceRecord' => AttendanceRecord::query()->with('student')->findOrFail($id)]);
    }

    public function updateAttendance(Request $request, int $id)
    {
        $data = $request->validate([
            'state' => ['required', Rule::in(array_column(AttendanceState::cases(), 'value'))],
            'reason' => ['required', 'string', 'max:2000'],
            'evidence' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);
        $record = AttendanceRecord::query()->findOrFail($id);
        abort_unless($this->scope->canReview(Auth::user(), $record), 403);
        $stored = null;

        try {
            if ($request->hasFile('evidence')) {
                $stored = $this->evidenceStorage->storeUploadedFile($request->file('evidence'));
            }
            $this->attendanceService->correctRecord(
                Auth::user(),
                $record,
                AttendanceState::from($data['state']),
                $data['reason'],
                $stored ?? [],
            );
        } catch (Throwable $exception) {
            if ($stored !== null) {
                $this->evidenceStorage->delete($stored['path'], $stored['disk']);
            }
            report($exception);

            return back()->with('error', __('attendance.update_failed'));
        }

        return redirect()->route('duty-teacher.attendance.index')->with('success', __('messages.updated', ['item' => __('labels.attendance')]));
    }

    public function exportAttendance(Request $request)
    {
        $attendanceRecords = $this->attendanceFilter->filter($request, $this->attendanceFilter->buildBaseQuery(), false);

        return Pdf::loadView('attendance-report', compact('attendanceRecords'))->download('attendance.pdf');
    }
}
