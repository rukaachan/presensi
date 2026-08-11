<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Models\Teacher;
use App\Services\AttendanceFilterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CounselingTeacherController extends Controller
{
    public function __construct(private AttendanceFilterService $attendanceFilter) {}

    public function index()
    {
        $stats = AttendanceRecord::query()
            ->selectRaw("SUM(CASE WHEN state = 'confirmed' THEN 1 ELSE 0 END) AS totalPresent")
            ->selectRaw("SUM(CASE WHEN state = 'excused' THEN 1 ELSE 0 END) AS totalExcused")
            ->selectRaw("SUM(CASE WHEN state = 'absent' THEN 1 ELSE 0 END) AS totalAbsent")
            ->first();

        return view('counseling.index', [
            'teacher' => Auth::user()->teacher()->first(),
            'totalPresent' => (int) ($stats->totalPresent ?? 0),
            'totalExcused' => (int) ($stats->totalExcused ?? 0),
            'totalAbsent' => (int) ($stats->totalAbsent ?? 0),
        ]);
    }

    public function showProfile(int $id)
    {
        return view('counseling.profile', ['teacher' => Teacher::query()->with('account.role')->findOrFail($id)]);
    }

    public function showAttendance(Request $request)
    {
        return view('counseling.attendance', [
            'attendanceRecords' => $this->attendanceFilter->filter($request, $this->attendanceFilter->buildBaseQuery()),
            'classrooms' => Classroom::query()->with('department')->orderBy('name')->get(),
        ]);
    }

    public function detailAttendance(int $id)
    {
        return view('counseling.attendance-detail', ['attendanceRecord' => AttendanceRecord::query()->with(['student.classroom.department', 'session'])->findOrFail($id)]);
    }

    public function exportAttendance(Request $request)
    {
        $attendanceRecords = $this->attendanceFilter->filter($request, $this->attendanceFilter->buildBaseQuery(), false);

        return Pdf::loadView('attendance-report', compact('attendanceRecords'))->download('counseling-attendance.pdf');
    }
}
