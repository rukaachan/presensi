<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Application bindings are resolved through Laravel's container.
    }

    public function boot(): void
    {
        Builder::macro('joinClassroom', function (): Builder {
            /** @var Builder $this */
            return $this->join('classrooms', 'students.classroom_id', '=', 'classrooms.id');
        });

        Builder::macro('joinClassroomTeacher', function (): Builder {
            /** @var Builder $this */
            return $this->joinClassroom()
                ->join('teachers', 'teachers.id', '=', 'classrooms.homeroom_teacher_id');
        });

        Builder::macro('joinStudent', function (): Builder {
            /** @var Builder $this */
            return $this->join('students', 'attendance_records.student_id', '=', 'students.id');
        });

        Builder::macro('joinStudentClassroom', function (): Builder {
            /** @var Builder $this */
            return $this->joinStudent()
                ->join('classrooms', 'students.classroom_id', '=', 'classrooms.id');
        });

        Builder::macro('joinStudentClassroomTeacher', function (): Builder {
            /** @var Builder $this */
            return $this->joinStudentClassroom()
                ->join('teachers', 'teachers.id', '=', 'classrooms.homeroom_teacher_id');
        });

        Builder::macro('joinStudentClassroomDepartment', function (): Builder {
            /** @var Builder $this */
            return $this->joinStudentClassroom()
                ->join('departments', 'classrooms.department_id', '=', 'departments.id');
        });
    }
}
