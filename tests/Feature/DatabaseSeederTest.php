<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    public function test_local_database_seeder_is_repeatable_and_contains_operational_demo_data(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->assertSame(Command::SUCCESS, Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]));

        $this->assertDatabaseHas('akun', ['username' => 'tu.demo']);
        $this->assertTrue(Hash::check('password123', (string) DB::table('akun')->where('username', 'tu.demo')->value('password')));
        $this->assertSame(6, DB::table('role_akun')->count());
        $this->assertSame(33, DB::table('akun')->count());
        $this->assertSame(8, DB::table('guru')->count());
        $this->assertSame(8, DB::table('kelas')->count());
        $this->assertSame(24, DB::table('siswa')->count());
        $this->assertSame(8, DB::table('pengurus_kelas')->count());
        $this->assertSame(21, DB::table('presensi_siswa')->where('tanggal', CarbonImmutable::now('Asia/Jakarta')->toDateString())->count());
        $this->assertGreaterThan(0, DB::table('validasi')->where('status_validasi', 'tidak_ada')->count());
        $this->assertGreaterThan(0, DB::table('logs')->count());

        $countsBeforeReseed = $this->tableCounts();

        $this->assertSame(Command::SUCCESS, Artisan::call('db:seed', ['--force' => true]));
        $this->assertSame($countsBeforeReseed, $this->tableCounts());
    }

    private function tableCounts(): array
    {
        return [
            'roles' => DB::table('role_akun')->count(),
            'accounts' => DB::table('akun')->count(),
            'teachers' => DB::table('guru')->count(),
            'classes' => DB::table('kelas')->count(),
            'students' => DB::table('siswa')->count(),
            'officers' => DB::table('pengurus_kelas')->count(),
            'attendance' => DB::table('presensi_siswa')->count(),
            'validation' => DB::table('validasi')->count(),
            'logs' => DB::table('logs')->count(),
        ];
    }
}
