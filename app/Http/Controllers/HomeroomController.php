<?php

namespace App\Http\Controllers;

use App\Authorization\AttendanceScope;
use App\Domain\Attendance\AttendanceState;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Account;
use App\Models\AttendanceRecord;
use App\Models\AuditEvent;
use App\Models\ClassOfficer;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\AttendanceFilterService;
use App\Services\AttendanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class HomeroomController extends Controller
{
    public function __construct(private AttendanceService $attendanceService, private AttendanceFilterService $attendanceFilter, private AttendanceScope $scope) {}

    public function index()
    {
        /** @var Account $account */
        $account = Auth::user();
        /** @var Teacher $teacher */
        $teacher = $account->teacher()->with('homeroomClassrooms')->firstOrFail();
        $classroomIds = $teacher->homeroomClassrooms->pluck('id');
        $studentIds = Student::query()->whereIn('classroom_id', $classroomIds)->pluck('id');
        $stats = AttendanceRecord::query()->whereIn('student_id', $studentIds)
            ->selectRaw('COUNT(DISTINCT student_id) AS totalStudents')
            ->selectRaw("SUM(CASE WHEN state = 'confirmed' THEN 1 ELSE 0 END) AS totalPresent")
            ->selectRaw("SUM(CASE WHEN state = 'excused' THEN 1 ELSE 0 END) AS totalExcused")
            ->selectRaw("SUM(CASE WHEN state = 'absent' THEN 1 ELSE 0 END) AS totalAbsent")
            ->first();

        return view('homeroom.index', [
            'teacher' => $teacher,
            'totalStudent' => Student::query()->whereIn('classroom_id', $classroomIds)->where('status', 'active')->count(),
            'totalPresent' => (int) ($stats->totalPresent ?? 0),
            'totalExcused' => (int) ($stats->totalExcused ?? 0),
            'totalAbsent' => (int) ($stats->totalAbsent ?? 0),
        ]);
    }

    public function showProfile(int $id)
    {
        $teacher = Auth::user()->teacher()->with('account.role')->findOrFail($id);

        return view('homeroom.profile', compact('teacher'));
    }

    public function showStudent(Request $request)
    {
        $classroomIds = $this->classroomIds();
        $students = Student::query()->with(['classroom.department', 'classOfficers'])
            ->whereIn('classroom_id', $classroomIds)
            ->when($request->filled('keyword'), static fn ($query) => $query->where('name', 'like', '%'.$request->string('keyword').'%'))
            ->when($request->filled('classroom_id'), static fn ($query) => $query->where('classroom_id', (int) $request->input('classroom_id')))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('homeroom.students', ['students' => $students, 'classrooms' => Classroom::query()->whereIn('id', $classroomIds)->get()]);
    }

    public function detailStudent(int $id)
    {
        $student = Student::query()->with(['classroom.department', 'classOfficers'])->findOrFail($id);
        abort_unless($this->scope->canViewStudent(Auth::user(), $student), 403);

        return view('homeroom.student-detail', compact('student'));
    }

    public function editStudent(int $id)
    {
        return view('homeroom.edit-student', [
            'student' => Student::query()->findOrFail($id),
            'classrooms' => Classroom::query()->whereIn('id', $this->classroomIds())->get(),
        ]);
    }

    public function updateStudent(UpdateStudentRequest $request, int $id)
    {
        $student = Student::query()->findOrFail($id);
        abort_unless($this->scope->canViewStudent(Auth::user(), $student), 403);
        $data = $request->validated();
        $student->update([
            'student_number' => $data['student_number'],
            'name' => $data['name'],
            'classroom_id' => $data['classroom_id'],
            'gender' => $data['gender'],
            'phone' => $data['phone'],
            'admission_year' => $data['admission_year'],
        ]);

        return redirect()->route('homeroom.students.index')->with('success', __('messages.updated', ['item' => __('labels.student')]));
    }

    public function showClassOfficers()
    {
        return view('homeroom.class-officers', [
            'officers' => ClassOfficer::query()->with('student.classroom')->whereHas('student', fn ($student) => $student->whereIn('classroom_id', $this->classroomIds()))->paginate(25),
        ]);
    }

    public function createClassOfficer()
    {
        return view('homeroom.create-class-officer', ['students' => Student::query()->whereIn('classroom_id', $this->classroomIds())->where('position', 'student')->get()]);
    }

    public function storeClassOfficer(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'position' => ['required', Rule::in(['class_president', 'vice_president', 'secretary', 'treasurer', 'class_officer'])],
        ]);
        $student = Student::query()->findOrFail($data['student_id']);
        abort_unless($this->scope->canViewStudent(Auth::user(), $student), 403);
        ClassOfficer::query()->updateOrCreate(['student_id' => $student->getKey()], $data + ['created_by_label' => 'Homeroom teacher']);
        $student->update(['position' => $data['position']]);

        return redirect()->route('homeroom.class-officers.index')->with('success', __('messages.created', ['item' => __('labels.class_officer')]));
    }

    public function detailClassroomPengurus(int $id)
    {
        return view('homeroom.class-officer-detail', ['officer' => ClassOfficer::query()->with('student.classroom')->findOrFail($id)]);
    }

    public function editClassOfficer(int $id)
    {
        return view('homeroom.edit-class-officer', ['officer' => ClassOfficer::query()->with('student')->findOrFail($id)]);
    }

    public function updateClassOfficer(Request $request, int $id)
    {
        $data = $request->validate(['position' => ['required', Rule::in(['class_president', 'vice_president', 'secretary', 'treasurer', 'class_officer'])]]);
        $officer = ClassOfficer::query()->with('student')->findOrFail($id);
        abort_unless($officer->student instanceof Student && $this->scope->canViewStudent(Auth::user(), $officer->student), 403);
        $officer->update($data);
        $officer->student->update(['position' => $data['position']]);

        return redirect()->route('homeroom.class-officers.index')->with('success', __('messages.updated', ['item' => __('labels.class_officer')]));
    }

    public function destroyClassOfficer(Request $request)
    {
        $officer = ClassOfficer::query()->with('student')->findOrFail((int) $request->input('id'));
        $officer->student->update(['position' => 'student']);
        $officer->delete();

        return redirect()->route('homeroom.class-officers.index')->with('success', __('messages.deleted', ['item' => __('labels.class_officer')]));
    }

    public function showAttendance(Request $request)
    {
        $attendanceRecords = $this->attendanceFilter->filter($request, $this->attendanceFilter->buildBaseQuery()->whereHas('student', fn ($student) => $student->whereIn('classroom_id', $this->classroomIds())));

        return view('homeroom.attendance', compact('attendanceRecords'));
    }

    public function editAttendance(int $id)
    {
        return view('homeroom.edit-attendance', ['attendanceRecord' => AttendanceRecord::query()->with('student')->findOrFail($id)]);
    }

    public function updateAttendance(Request $request, int $id)
    {
        $data = $request->validate([
            'state' => ['required', Rule::enum(AttendanceState::class)],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $record = AttendanceRecord::query()->findOrFail($id);
        abort_unless($this->scope->canReview(Auth::user(), $record), 403);
        $this->attendanceService->correctRecord(Auth::user(), $record, AttendanceState::from($data['state']), $data['reason']);

        return redirect()->route('homeroom.attendance.index')->with('success', __('messages.updated', ['item' => __('labels.attendance')]));
    }

    public function exportAttendance(Request $request)
    {
        $attendanceRecords = $this->attendanceFilter->filter($request, $this->attendanceFilter->buildBaseQuery()->whereHas('student', fn ($student) => $student->whereIn('classroom_id', $this->classroomIds())), false);

        return Pdf::loadView('homeroom.attendance-report', compact('attendanceRecords'))->download('homeroom-attendance.pdf');
    }

    public function showAuditLog()
    {
        return view('homeroom.audits', ['audits' => AuditEvent::query()->latest('occurred_at')->simplePaginate(25)]);
    }

    private function classroomIds()
    {
        /** @var Account $account */
        $account = Auth::user();
        /** @var Teacher $teacher */
        $teacher = $account->teacher()->firstOrFail();

        return $teacher->homeroomClassrooms()->pluck('classrooms.id');
    }
}
