<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdministrationController extends Controller
{
    public function index()
    {
        $operationalDate = now((string) config('attendance.timezone', 'Asia/Jakarta'));
        $date = $operationalDate->toDateString();
        $ttl = 120;
        $sessionId = AttendanceSession::query()->where('code', 'daily_check_in')->value('id');

        $dailyStats = Cache::remember("dashboard:administration:daily:{$date}:v2", $ttl, static function () use ($date, $sessionId) {
            return DB::table('attendance_records')
                ->where('attendance_session_id', $sessionId)
                ->whereDate('attendance_date', $date)
                ->selectRaw('COUNT(DISTINCT student_id) AS totalRecorded')
                ->selectRaw("SUM(CASE WHEN state = 'confirmed' THEN 1 ELSE 0 END) AS totalPresent")
                ->selectRaw("SUM(CASE WHEN state = 'excused' THEN 1 ELSE 0 END) AS totalExcused")
                ->selectRaw("SUM(CASE WHEN state = 'absent' THEN 1 ELSE 0 END) AS totalAbsent")
                ->first();
        });

        $activeStudents = Cache::remember('dashboard:administration:active-students:v2', $ttl, static fn () => DB::table('students')->where('status', 'active')->count());
        $activeClasses = Cache::remember('dashboard:administration:active-classrooms:v2', $ttl, static fn () => DB::table('classrooms')->where('status', 'active')->count());
        $pendingEvents = Cache::remember("dashboard:administration:pending-events:{$date}:v2", $ttl, static fn () => DB::table('attendance_events')->whereDate('event_date', $date)->where('state', 'needs_review')->count());

        $classReadiness = Cache::remember("dashboard:administration:classrooms:{$date}:v2", $ttl, static function () use ($date, $sessionId) {
            return DB::table('classrooms')
                ->join('departments', 'departments.id', '=', 'classrooms.department_id')
                ->leftJoin('students', function ($join): void {
                    $join->on('students.classroom_id', '=', 'classrooms.id')->where('students.status', 'active');
                })
                ->leftJoin('attendance_records', function ($join) use ($date, $sessionId): void {
                    $join->on('attendance_records.student_id', '=', 'students.id')
                        ->whereDate('attendance_records.attendance_date', $date)
                        ->where('attendance_records.attendance_session_id', $sessionId);
                })
                ->where('classrooms.status', 'active')
                ->groupBy('classrooms.id', 'classrooms.grade_level', 'departments.name', 'classrooms.name')
                ->orderByDesc('classrooms.grade_level')->orderBy('departments.name')->orderBy('classrooms.name')
                ->get([
                    'classrooms.id',
                    'classrooms.grade_level',
                    'departments.name as department_name',
                    'classrooms.name as classroom_name',
                    DB::raw('COUNT(DISTINCT students.id) AS total_students'),
                    DB::raw('COUNT(DISTINCT attendance_records.student_id) AS total_recorded'),
                ])
                ->map(static function ($class) {
                    $class->completionRate = (int) ($class->total_students > 0
                        ? round(((int) $class->total_recorded / (int) $class->total_students) * 100)
                        : 0);
                    $class->isComplete = (int) $class->total_students > 0
                        && (int) $class->total_recorded >= (int) $class->total_students;

                    return $class;
                });
        });

        $totalRecorded = (int) ($dailyStats->totalRecorded ?? 0);
        $totalActiveStudents = (int) $activeStudents;
        $dailySummary = [
            'totalActiveStudents' => $totalActiveStudents,
            'totalActiveClasses' => (int) $activeClasses,
            'totalRecorded' => $totalRecorded,
            'totalMissing' => max(0, $totalActiveStudents - $totalRecorded),
            'totalPresent' => (int) ($dailyStats->totalPresent ?? 0),
            'totalExcused' => (int) ($dailyStats->totalExcused ?? 0),
            'totalAbsent' => (int) ($dailyStats->totalAbsent ?? 0),
            'pendingValidation' => (int) $pendingEvents,
            'needsReview' => (int) $pendingEvents + (int) ($dailyStats->totalAbsent ?? 0),
            'completionRate' => $totalActiveStudents > 0 ? (int) round(($totalRecorded / $totalActiveStudents) * 100) : 0,
            'classesComplete' => $classReadiness->where('isComplete', true)->count(),
        ];
        $recentAudits = Cache::remember('dashboard:administration:recent-audits:v2', $ttl, static fn () => DB::table('audit_events')->latest('occurred_at')->limit(5)->get());

        return view('administration.index', compact('classReadiness', 'dailySummary', 'recentAudits', 'operationalDate'));
    }
}
