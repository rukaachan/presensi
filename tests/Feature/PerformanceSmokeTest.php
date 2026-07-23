<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Support\PerformanceFixture;
use Tests\TestCase;

class PerformanceSmokeTest extends TestCase
{
    private array $accounts;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        PerformanceFixture::createSchema();
        $this->accounts = PerformanceFixture::seed();
    }

    public function test_all_role_dashboards_render_with_lowercase_attendance_totals(): void
    {
        $dashboards = [
            'tata_usaha' => ['/tata-usaha/dashboard', 'tata-usaha.index'],
            'guru_bk' => ['/guru-bk/dashboard', 'guru-bk.index'],
            'guru_piket' => ['/guru-piket/dashboard', 'guru-piket.index'],
            'pengurus' => ['/pengurus-kelas/dashboard', 'pengurus-kelas.index'],
            'wali' => ['/wali-kelas/dashboard', 'wali-kelas.index'],
            'siswa' => ['/siswa/dashboard', 'siswa.index'],
        ];

        foreach ($dashboards as $role => [$uri, $view]) {
            $response = $this->actingAs($this->account($role))->get($uri);

            $response->assertOk()->assertViewIs($view);
        }

        $this->actingAs($this->account('siswa'))
            ->get('/siswa/dashboard')
            ->assertViewHas('totalHadir', 2)
            ->assertViewHas('totalIzin', 1)
            ->assertViewHas('totalAlpha', 1);

        $this->actingAs($this->account('pengurus'))
            ->get('/pengurus-kelas/dashboard')
            ->assertViewHas('totalHadir', 2)
            ->assertViewHas('totalIzin', 1)
            ->assertViewHas('totalAlpha', 1);

        foreach (['guru_bk' => '/guru-bk/dashboard', 'guru_piket' => '/guru-piket/dashboard'] as $role => $uri) {
            $this->actingAs($this->account($role))
                ->get($uri)
                ->assertViewHas('totalHadir', 4)
                ->assertViewHas('totalIzin', 2)
                ->assertViewHas('totalAlpha', 2);
        }
    }

    public function test_photo_attendance_pages_render_role_specific_endpoints(): void
    {
        $this->actingAs($this->account('siswa'))
            ->get('/siswa/presensi')
            ->assertOk()
            ->assertSee(route('siswa.webcam.capture'), false)
            ->assertSee(route('siswa.webcam.check_snapshot'), false);

        $this->actingAs($this->account('pengurus'))
            ->get('/pengurus-kelas/presensi')
            ->assertOk()
            ->assertSee(route('pengurus-kelas.webcam.capture'), false)
            ->assertSee(route('pengurus-kelas.webcam.check_snapshot'), false);
    }

    public function test_dashboard_query_budgets_cover_rendered_http_responses(): void
    {
        $budgets = [
            'tata_usaha' => ['/tata-usaha/dashboard', 3],
            'guru_bk' => ['/guru-bk/dashboard', 2],
            'guru_piket' => ['/guru-piket/dashboard', 2],
            'pengurus' => ['/pengurus-kelas/dashboard', 1],
            'wali' => ['/wali-kelas/dashboard', 3],
            'siswa' => ['/siswa/dashboard', 2],
        ];

        $queries = [];
        DB::listen(static function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        foreach ($budgets as $role => [$uri, $budget]) {
            $account = $this->account($role);
            Cache::flush();
            $queries = [];

            $this->actingAs($account)->get($uri)->assertOk();

            $this->assertLessThanOrEqual(
                $budget,
                count($queries),
                "$uri exceeded its $budget-query budget:\n".implode("\n", $queries)
            );
        }
    }

    private function account(string $role): Akun
    {
        return Akun::findOrFail($this->accounts[$role]);
    }
}
