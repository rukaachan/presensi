<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CanonicalDatabase;
use Tests\TestCase;

class CanonicalJoinIntegrityTest extends TestCase
{
    use CanonicalDatabase;

    public function test_attendance_records_join_to_their_student_classroom_and_department(): void
    {
        $this->seedCanonicalDatabase();
        $rows = DB::table('attendance_records')
            ->join('students', 'attendance_records.student_id', '=', 'students.id')
            ->join('classrooms', 'students.classroom_id', '=', 'classrooms.id')
            ->join('departments', 'classrooms.department_id', '=', 'departments.id')
            ->select('attendance_records.id', 'students.name as student_name', 'classrooms.name as classroom_name', 'departments.name as department_name')
            ->get();

        $this->assertCount(AttendanceRecord::query()->count(), $rows);
        $this->assertNotNull($rows->first()->student_name);
        $this->assertNotNull($rows->first()->classroom_name);
        $this->assertNotNull($rows->first()->department_name);
    }

    public function test_removed_indonesian_tables_are_not_available_after_migration(): void
    {
        $this->seedCanonicalDatabase();
        foreach (['akun', 'guru', 'kelas', 'jurusan', 'siswa', 'presensi_siswa', 'validasi', 'surat_keterangan', 'logs'] as $table) {
            $this->assertFalse(Schema::hasTable($table), $table.' should not exist after the English migration.');
        }
    }
}
