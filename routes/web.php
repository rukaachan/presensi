<?php

use App\Http\Controllers\Administration\AttendanceController as AdministrationAttendanceController;
use App\Http\Controllers\Administration\AuditController as AdministrationAuditController;
use App\Http\Controllers\Administration\ClassOfficerController as AdministrationClassOfficerController;
use App\Http\Controllers\Administration\ClassroomController as AdministrationClassroomController;
use App\Http\Controllers\Administration\DepartmentController as AdministrationDepartmentController;
use App\Http\Controllers\Administration\StudentController as AdministrationStudentController;
use App\Http\Controllers\Administration\TeacherController as AdministrationTeacherController;
use App\Http\Controllers\AdministrationController;
use App\Http\Controllers\AttendanceEvidenceController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\ClassOfficerController;
use App\Http\Controllers\CounselingTeacherController;
use App\Http\Controllers\DutyTeacherController;
use App\Http\Controllers\HomeroomController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthenticationController::class, 'index'])->name('login');
Route::post('/', [AuthenticationController::class, 'authenticate']);
Route::post('/logout', [AuthenticationController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('attendance/evidence/{attendanceRecord}', [AttendanceEvidenceController::class, 'show'])
        ->name('attendance.evidence');

    Route::prefix('administration')->middleware('role:administrator')->group(function (): void {
        Route::get('dashboard', [AdministrationController::class, 'index'])->name('administration.dashboard');

        Route::get('departments', [AdministrationDepartmentController::class, 'showDepartment'])->name('administration.departments.index');
        Route::get('departments/create', [AdministrationDepartmentController::class, 'createDepartment'])->name('administration.departments.create');
        Route::post('departments', [AdministrationDepartmentController::class, 'storeDepartment'])->name('administration.departments.store');
        Route::get('departments/{id}/edit', [AdministrationDepartmentController::class, 'editDepartment'])->name('administration.departments.edit');
        Route::put('departments/{id}', [AdministrationDepartmentController::class, 'updateDepartment'])->name('administration.departments.update');
        Route::delete('departments', [AdministrationDepartmentController::class, 'destroyDepartment'])->name('administration.departments.destroy');

        Route::get('classrooms', [AdministrationClassroomController::class, 'showClassroom'])->name('administration.classrooms.index');
        Route::get('classrooms/create', [AdministrationClassroomController::class, 'createClassroom'])->name('administration.classrooms.create');
        Route::get('classrooms/{id}', [AdministrationClassroomController::class, 'detailClassroom'])->name('administration.classrooms.show');
        Route::post('classrooms', [AdministrationClassroomController::class, 'storeClassroom'])->name('administration.classrooms.store');
        Route::get('classrooms/{id}/edit', [AdministrationClassroomController::class, 'editClassroom'])->name('administration.classrooms.edit');
        Route::put('classrooms/{id}', [AdministrationClassroomController::class, 'updateClassroom'])->name('administration.classrooms.update');
        Route::delete('classrooms', [AdministrationClassroomController::class, 'destroyClassroom'])->name('administration.classrooms.destroy');

        Route::get('teachers', [AdministrationTeacherController::class, 'showTeacher'])->name('administration.teachers.index');
        Route::get('teachers/create', [AdministrationTeacherController::class, 'createTeacher'])->name('administration.teachers.create');
        Route::get('teachers/{id}', [AdministrationTeacherController::class, 'detailTeacher'])->name('administration.teachers.show');
        Route::post('teachers', [AdministrationTeacherController::class, 'storeTeacher'])->name('administration.teachers.store');
        Route::get('teachers/{id}/edit', [AdministrationTeacherController::class, 'editTeacher'])->name('administration.teachers.edit');
        Route::put('teachers/{id}', [AdministrationTeacherController::class, 'updateTeacher'])->name('administration.teachers.update');
        Route::delete('teachers', [AdministrationTeacherController::class, 'destroyTeacher'])->name('administration.teachers.destroy');

        Route::get('class-officers', [AdministrationClassOfficerController::class, 'showClassOfficers'])->name('administration.class-officers.index');
        Route::get('class-officers/create', [AdministrationClassOfficerController::class, 'createClassOfficer'])->name('administration.class-officers.create');
        Route::get('class-officers/{id}', [AdministrationClassOfficerController::class, 'detailClassOfficer'])->name('administration.class-officers.show');
        Route::post('class-officers', [AdministrationClassOfficerController::class, 'storeClassOfficer'])->name('administration.class-officers.store');
        Route::get('class-officers/{id}/edit', [AdministrationClassOfficerController::class, 'editClassOfficer'])->name('administration.class-officers.edit');
        Route::put('class-officers/{id}', [AdministrationClassOfficerController::class, 'updateClassOfficer'])->name('administration.class-officers.update');
        Route::delete('class-officers', [AdministrationClassOfficerController::class, 'destroyClassOfficer'])->name('administration.class-officers.destroy');

        Route::get('students', [AdministrationStudentController::class, 'showStudent'])->name('administration.students.index');
        Route::get('students/create', [AdministrationStudentController::class, 'createStudent'])->name('administration.students.create');
        Route::get('students/{id}', [AdministrationStudentController::class, 'detailStudent'])->name('administration.students.show');
        Route::post('students', [AdministrationStudentController::class, 'storeStudent'])->name('administration.students.store');
        Route::get('students/{id}/edit', [AdministrationStudentController::class, 'editStudent'])->name('administration.students.edit');
        Route::put('students/{id}', [AdministrationStudentController::class, 'updateStudent'])->name('administration.students.update');
        Route::delete('students', [AdministrationStudentController::class, 'destroyStudent'])->name('administration.students.destroy');

        Route::get('attendance', [AdministrationAttendanceController::class, 'showAttendance'])->name('administration.attendance.index');
        Route::get('attendance/pdf', [AdministrationAttendanceController::class, 'exportAttendance'])->name('administration.attendance.pdf');
        Route::get('audits', [AdministrationAuditController::class, 'showAuditLogs'])->name('administration.audits.index');
        Route::post('audits/archive', [AdministrationAuditController::class, 'deleteAuditEvents'])->name('administration.audits.archive');
    });

    Route::prefix('counseling')->middleware('role:counseling_teacher')->group(function (): void {
        Route::get('dashboard', [CounselingTeacherController::class, 'index'])->name('counseling.dashboard');
        Route::get('profile/{id}', [CounselingTeacherController::class, 'showProfile'])->name('counseling.profile.show');
        Route::get('attendance', [CounselingTeacherController::class, 'showAttendance'])->name('counseling.attendance.index');
        Route::get('attendance/pdf', [CounselingTeacherController::class, 'exportAttendance'])->name('counseling.attendance.pdf');
        Route::get('attendance/{id}', [CounselingTeacherController::class, 'detailAttendance'])->name('counseling.attendance.show');
    });

    Route::prefix('duty-teacher')->middleware('role:duty_teacher')->group(function (): void {
        Route::get('dashboard', [DutyTeacherController::class, 'index'])->name('duty-teacher.dashboard');
        Route::get('profile/{id}', [DutyTeacherController::class, 'showProfile'])->name('duty-teacher.profile.show');
        Route::get('class-officers', [DutyTeacherController::class, 'showClassOfficers'])->name('duty-teacher.class-officers.index');
        Route::get('class-officers/{id}', [DutyTeacherController::class, 'detailClassOfficer'])->name('duty-teacher.class-officers.show');
        Route::get('attendance', [DutyTeacherController::class, 'showAttendance'])->name('duty-teacher.attendance.index');
        Route::get('attendance/pdf', [DutyTeacherController::class, 'exportAttendance'])->name('duty-teacher.attendance.pdf');
        Route::get('attendance/{id}', [DutyTeacherController::class, 'detailAttendance'])->name('duty-teacher.attendance.show');
        Route::get('attendance/{id}/edit', [DutyTeacherController::class, 'editAttendance'])->name('duty-teacher.attendance.edit');
        Route::put('attendance/{id}', [DutyTeacherController::class, 'updateAttendance'])->name('duty-teacher.attendance.update');
    });

    Route::prefix('class-officer')->middleware('role:class_officer')->group(function (): void {
        Route::get('dashboard', [ClassOfficerController::class, 'index'])->name('class-officer.dashboard');
        Route::get('profile/{id}', [ClassOfficerController::class, 'showProfile'])->name('class-officer.profile.show');
        Route::get('history', [ClassOfficerController::class, 'showHistory'])->name('class-officer.history.index');
        Route::get('attendance', [ClassOfficerController::class, 'openCamera'])->name('class-officer.attendance.create');
        Route::post('attendance', [ClassOfficerController::class, 'store'])->name('class-officer.attendance.store');
        Route::post('attendance/check', [ClassOfficerController::class, 'checkCapture'])->name('class-officer.attendance.check');
        Route::get('events', [ClassOfficerController::class, 'showClassroom'])->name('class-officer.events.index');
        Route::post('events/suggestions', [ClassOfficerController::class, 'suggestAttendanceEvent'])->name('class-officer.events.suggest');
        Route::get('attendance/pdf', [ClassOfficerController::class, 'exportAttendance'])->name('class-officer.attendance.pdf');
        Route::get('events/pdf', [ClassOfficerController::class, 'exportClassroom'])->name('class-officer.events.pdf');
    });

    Route::prefix('homeroom')->middleware('role:homeroom_teacher')->group(function (): void {
        Route::get('dashboard', [HomeroomController::class, 'index'])->name('homeroom.dashboard');
        Route::get('profile/{id}', [HomeroomController::class, 'showProfile'])->name('homeroom.profile.show');
        Route::get('students', [HomeroomController::class, 'showStudent'])->name('homeroom.students.index');
        Route::get('students/{id}', [HomeroomController::class, 'detailStudent'])->name('homeroom.students.show');
        Route::get('students/{id}/edit', [HomeroomController::class, 'editStudent'])->name('homeroom.students.edit');
        Route::put('students/{id}', [HomeroomController::class, 'updateStudent'])->name('homeroom.students.update');
        Route::get('class-officers', [HomeroomController::class, 'showClassOfficers'])->name('homeroom.class-officers.index');
        Route::get('class-officers/create', [HomeroomController::class, 'createClassOfficer'])->name('homeroom.class-officers.create');
        Route::post('class-officers', [HomeroomController::class, 'storeClassOfficer'])->name('homeroom.class-officers.store');
        Route::get('class-officers/{id}', [HomeroomController::class, 'detailClassroomPengurus'])->name('homeroom.class-officers.show');
        Route::get('class-officers/{id}/edit', [HomeroomController::class, 'editClassOfficer'])->name('homeroom.class-officers.edit');
        Route::put('class-officers/{id}', [HomeroomController::class, 'updateClassOfficer'])->name('homeroom.class-officers.update');
        Route::delete('class-officers', [HomeroomController::class, 'destroyClassOfficer'])->name('homeroom.class-officers.destroy');
        Route::get('attendance', [HomeroomController::class, 'showAttendance'])->name('homeroom.attendance.index');
        Route::get('attendance/{id}/edit', [HomeroomController::class, 'editAttendance'])->name('homeroom.attendance.edit');
        Route::put('attendance/{id}', [HomeroomController::class, 'updateAttendance'])->name('homeroom.attendance.update');
        Route::get('attendance/pdf', [HomeroomController::class, 'exportAttendance'])->name('homeroom.attendance.pdf');
        Route::get('audits', [HomeroomController::class, 'showAuditLog'])->name('homeroom.audits.index');
    });

    Route::prefix('student')->middleware('role:student')->group(function (): void {
        Route::get('dashboard', [StudentController::class, 'index'])->name('student.dashboard');
        Route::get('profile/{id}', [StudentController::class, 'showProfile'])->name('student.profile.show');
        Route::get('history', [StudentController::class, 'showHistory'])->name('student.history.index');
        Route::get('attendance', [StudentController::class, 'openCamera'])->name('student.attendance.create');
        Route::post('attendance', [StudentController::class, 'store'])->name('student.attendance.store');
        Route::post('attendance/check', [StudentController::class, 'checkCapture'])->name('student.attendance.check');
        Route::get('attendance/pdf', [StudentController::class, 'exportAttendance'])->name('student.attendance.pdf');
    });
});
