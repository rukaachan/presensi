<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
use App\Services\AttendanceService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendancePerformanceRegressionTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_daily_capture_stays_within_the_domain_query_budget(): void
    {
        $this->seedDatabase();
        $account = $this->account('siswa.demo');
        $student = $this->studentFor($account);
        $this->removeToday($student);
        DB::table('attendance_events')->delete();
        DB::table('attendance_records')->delete();

        $queries = $this->measureQueries(
            fn (): mixed => app(AttendanceService::class)->recordDailyCheckIn($account, $student),
        );

        $this->assertLessThanOrEqual(10, count($queries), implode("\n", $queries));
    }

    public function test_optional_event_capture_stays_within_the_domain_query_budget(): void
    {
        $this->seedDatabase();
        $account = $this->account('pengurus.demo');
        $officer = $this->studentFor($account);
        $target = Siswa::query()
            ->where('id_kelas', $officer->id_kelas)
            ->where('id_siswa', '!=', $officer->getKey())
            ->firstOrFail();
        DB::table('attendance_events')->delete();

        $queries = $this->measureQueries(
            fn (): mixed => app(AttendanceService::class)->recordOptionalEvent($account, $target, 'break_1'),
        );

        $this->assertLessThanOrEqual(12, count($queries), implode("\n", $queries));
    }

    public function test_legacy_attendance_list_keeps_a_small_read_query_budget(): void
    {
        $this->seedDatabase();
        $queries = $this->measureQueries(function (): void {
            $this->actingAs($this->account('piket.demo'))
                ->get(route('guru-piket.presensi.index'))
                ->assertOk();
        });

        $this->assertLessThanOrEqual(5, count($queries), implode("\n", $queries));
    }

    /**
     * @return list<string>
     */
    private function measureQueries(\Closure $callback): array
    {
        $queries = [];
        DB::listen(static function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });
        $callback();

        return $queries;
    }

    private function seedDatabase(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-02-22 08:00:00', 'Asia/Jakarta'));
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->assertSame(Command::SUCCESS, Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]));
    }

    private function account(string $username): Akun
    {
        return Akun::query()->where('username', $username)->firstOrFail();
    }

    private function studentFor(Akun $account): Siswa
    {
        return Siswa::query()->where('id_akun', $account->getKey())->firstOrFail();
    }

    private function removeToday(Siswa $student): void
    {
        PresensiSiswa::query()
            ->where('id_siswa', $student->getKey())
            ->whereDate('tanggal', now('Asia/Jakarta')->toDateString())
            ->delete();
    }
}
