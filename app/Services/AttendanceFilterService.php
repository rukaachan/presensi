<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AttendanceFilterService
{
    public function buildBaseQuery(?AttendanceRecord $attendanceRecord = null): Builder
    {
        return AttendanceRecord::query()
            ->with(['student.classroom.department', 'session'])
            ->orderBy('attendance_date')
            ->orderBy('id');
    }

    public function filter(Request $request, Builder $query, bool $usePagination = true, array $options = [])
    {
        $keyword = trim((string) $request->input($options['keyword_key'] ?? 'keyword'));
        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword): void {
                $builder->whereHas('student', function (Builder $student) use ($keyword): void {
                    $student->where('name', 'like', "%{$keyword}%")
                        ->orWhere('student_number', 'like', "%{$keyword}%")
                        ->orWhereHas('classroom', function (Builder $classroom) use ($keyword): void {
                            $classroom->where('name', 'like', "%{$keyword}%")
                                ->orWhereHas('department', static fn (Builder $department): Builder => $department->where('name', 'like', "%{$keyword}%"));
                        });
                })->orWhere('state', 'like', "%{$keyword}%");
            });
        }

        $monthKey = (string) ($options['month_key'] ?? 'month');
        if ($request->filled($monthKey)) {
            $query->whereMonth('attendance_date', (int) $request->input($monthKey));
        }

        $dateKey = (string) ($options['date_key'] ?? 'date');
        if ($request->filled($dateKey)) {
            $query->whereDate('attendance_date', (string) $request->input($dateKey));
        }

        $stateKey = (string) ($options['state_key'] ?? 'state');
        if ($request->filled($stateKey)) {
            $query->where('state', (string) $request->input($stateKey));
        }

        $classroomKey = (string) ($options['classroom_key'] ?? 'classroom_id');
        if ($request->filled($classroomKey)) {
            $classroomId = (int) $request->input($classroomKey);
            $query->whereHas('student', static fn (Builder $student): Builder => $student->where('classroom_id', $classroomId));
        }

        $query->orderBy($options['order_column'] ?? 'attendance_date', $options['order_direction'] ?? 'desc');

        if ($usePagination) {
            return $query->simplePaginate($options['per_page'] ?? 25)->withQueryString();
        }

        return $query->get();
    }
}
