<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;
use Tests\Support\CanonicalDatabase;
use Tests\TestCase;

class PerformanceSmokeTest extends TestCase
{
    use CanonicalDatabase;

    public function test_role_dashboards_render_with_canonical_queries(): void
    {
        $this->seedCanonicalDatabase();
        foreach ([
            ['administrator.demo', 'administration.dashboard'],
            ['homeroom.demo', 'homeroom.dashboard'],
            ['officer.demo', 'class-officer.dashboard'],
            ['duty.demo', 'duty-teacher.dashboard'],
            ['counseling.demo', 'counseling.dashboard'],
            ['student.demo', 'student.dashboard'],
        ] as [$username, $route]) {
            $this->actingAs($this->account($username))->get(route($route))->assertOk();
        }
    }

    public function test_attendance_filter_uses_bounded_query_count(): void
    {
        $this->seedCanonicalDatabase();
        $queries = [];
        DB::listen(static function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->actingAs($this->account('duty.demo'))->get(route('duty-teacher.attendance.index', ['state' => 'confirmed']))->assertOk();

        $this->assertLessThanOrEqual(20, count($queries), implode("\n", $queries));
        $this->assertGreaterThan(0, AttendanceRecord::query()->count());
    }
}
