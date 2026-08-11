<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CanonicalDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use CanonicalDatabase;

    public function test_database_seeder_is_repeatable_and_contains_canonical_demo_data(): void
    {
        $this->seedCanonicalDatabase();
        $this->assertDatabaseHas('accounts', ['username' => 'administrator.demo']);
        $this->assertDatabaseHas('roles', ['code' => 'student']);
        $this->assertSame(6, DB::table('roles')->count());
        $this->assertSame(33, DB::table('accounts')->count());
        $this->assertSame(8, DB::table('teachers')->count());
        $this->assertSame(8, DB::table('classrooms')->count());
        $this->assertSame(24, DB::table('students')->count());
        $this->assertSame(8, DB::table('class_officers')->count());
        $this->assertGreaterThan(0, DB::table('attendance_records')->count());
        $this->assertGreaterThan(0, DB::table('attendance_events')->count());
        foreach (['akun', 'siswa', 'guru', 'kelas', 'jurusan', 'presensi_siswa', 'validasi', 'logs'] as $table) {
            $this->assertFalse(Schema::hasTable($table), $table.' should not exist after the English migration.');
        }

        $counts = $this->canonicalCounts();
        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();
        $this->assertSame($counts, $this->canonicalCounts());
    }

    /** @return array<string, int> */
    private function canonicalCounts(): array
    {
        return collect(['roles', 'accounts', 'teachers', 'classrooms', 'students', 'class_officers', 'attendance_records', 'attendance_events', 'audit_events'])
            ->mapWithKeys(static fn (string $table): array => [$table => DB::table($table)->count()])
            ->all();
    }
}
