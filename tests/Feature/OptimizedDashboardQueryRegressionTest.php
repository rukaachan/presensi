<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Support\CanonicalDatabase;
use Tests\TestCase;

class OptimizedDashboardQueryRegressionTest extends TestCase
{
    use CanonicalDatabase;

    public function test_administration_dashboard_exposes_readiness_metrics(): void
    {
        $this->seedCanonicalDatabase();
        $response = $this->actingAs($this->account('administrator.demo'))->get(route('administration.dashboard'));

        $response->assertOk()->assertViewIs('administration.index')->assertViewHas('dailySummary', static function (array $summary): bool {
            return array_key_exists('completionRate', $summary) && array_key_exists('classesComplete', $summary);
        })->assertViewHas('classReadiness');
    }

    public function test_dashboard_queries_use_canonical_tables_only(): void
    {
        $this->seedCanonicalDatabase();
        $queries = [];
        DB::listen(static function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });
        $this->actingAs($this->account('administrator.demo'))->get(route('administration.dashboard'))->assertOk();

        $sql = implode("\n", $queries);
        foreach (['accounts', 'students', 'classrooms', 'attendance_records', 'audit_events'] as $table) {
            $this->assertStringContainsString($table, $sql);
        }
        foreach (['akun', 'siswa', 'kelas', 'presensi_siswa', 'logs'] as $table) {
            $this->assertStringNotContainsString('"'.$table.'"', $sql);
        }
    }
}
