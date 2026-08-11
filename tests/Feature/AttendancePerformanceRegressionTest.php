<?php

namespace Tests\Feature;

use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;
use Tests\Support\CanonicalDatabase;
use Tests\TestCase;

class AttendancePerformanceRegressionTest extends TestCase
{
    use CanonicalDatabase;

    public function test_record_and_event_relations_are_eager_loaded_for_lists(): void
    {
        $this->seedCanonicalDatabase();
        $queries = [];
        DB::listen(static function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $records = AttendanceRecord::query()->with(['student.classroom.department', 'session'])->latest('attendance_date')->limit(25)->get();
        $events = AttendanceEvent::query()->with(['student', 'session'])->latest('event_date')->limit(25)->get();

        $this->assertNotEmpty($records);
        $this->assertNotEmpty($events);
        $this->assertLessThanOrEqual(12, count($queries), implode("\n", $queries));
    }

    public function test_canonical_dashboard_does_not_reference_removed_tables(): void
    {
        $this->seedCanonicalDatabase();
        $queries = [];
        DB::listen(static function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });
        $this->actingAs($this->account('administrator.demo'))->get(route('administration.dashboard'))->assertOk();

        $joined = implode("\n", $queries);
        $this->assertStringNotContainsString('presensi_siswa', $joined);
        $this->assertStringNotContainsString('validasi', $joined);
    }
}
