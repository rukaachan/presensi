<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Services\AttendanceFilterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceFilterService $attendanceFilterService) {}

    public function showAttendance(Request $request)
    {
        $attendanceRecords = $this->attendanceFilterService->filter(
            $request,
            $this->attendanceFilterService->buildBaseQuery(),
        );

        return view('administration.attendance', [
            'attendanceRecords' => $attendanceRecords,
            'classrooms' => Classroom::query()->with('department')->orderBy('name')->get(),
        ]);
    }

    public function exportAttendance(Request $request)
    {
        $attendanceRecords = $this->attendanceFilterService->filter(
            $request,
            $this->attendanceFilterService->buildBaseQuery(),
            false,
        );

        return Pdf::loadView('attendance-report', compact('attendanceRecords'))->download('attendance.pdf');
    }
}
