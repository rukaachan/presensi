<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Akun;
use App\Models\PresensiSiswa;
use App\Models\Validasi;
use App\Services\AttendanceSessionCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendanceSessionPolicyTest extends TestCase
{
    public function test_hybrid_catalog_has_one_required_check_in_and_optional_legacy_sessions(): void
    {
        $this->seedDatabase();

        $catalog = app(AttendanceSessionCatalog::class);

        $this->assertSame('daily_check_in', $catalog->required()?->code);
        $this->assertSame(
            ['istirahat_pertama', 'istirahat_kedua', 'istirahat_ketiga'],
            $catalog->validationCodes(),
        );
        $this->assertSame(1, $catalog->active()->where('required', true)->count());
        $this->assertSame(3, $catalog->active()->where('required', false)->count());
    }

    public function test_retention_defaults_are_explicit_and_configurable(): void
    {
        $this->assertSame(1825, config('attendance.retention.attendance_days'));
        $this->assertSame(90, config('attendance.retention.evidence_days'));
        $this->assertSame(365, config('attendance.retention.leave_attachment_days'));
        $this->assertSame(730, config('attendance.retention.audit_days'));
        $this->assertSame(30, config('attendance.retention.notification_days'));
    }

    public function test_pengurus_validation_ui_reads_active_session_catalog(): void
    {
        $this->seedDatabase();

        $response = $this->actingAs(Akun::query()->where('username', 'pengurus.demo')->firstOrFail())
            ->get('/pengurus-kelas/kelas');

        $response
            ->assertOk()
            ->assertSee('Istirahat pertama')
            ->assertSee('Istirahat kedua')
            ->assertSee('Istirahat ketiga');
    }

    public function test_validation_update_records_a_class_officer_proposal(): void
    {
        $this->seedDatabase();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $validation = Validasi::query()->where('status_validasi', 'hadir')->firstOrFail();
        $legacy = PresensiSiswa::query()->findOrFail($validation->id_presensi);

        $response = $this->actingAs(Akun::query()->where('username', 'pengurus.demo')->firstOrFail())
            ->post('/pengurus-kelas/update-validasi', [
                'waktu_validasi' => $validation->waktu_validasi,
                'id_pengurus' => [$validation->id_pengurus],
                'id_presensi' => [$legacy->getKey()],
                'status_validasi' => ['0' => ['alpha']],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendance_events', [
            'student_id' => $legacy->id_siswa,
            'proposed_status' => 'alpha',
        ]);
    }

    public function test_validation_update_rejects_unknown_session_codes(): void
    {
        $this->seedDatabase();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $response = $this->actingAs(Akun::query()->where('username', 'pengurus.demo')->firstOrFail())
            ->post('/pengurus-kelas/update-validasi', [
                'waktu_validasi' => 'legacy_unknown',
                'status_validasi' => ['1' => ['hadir']],
            ]);

        $response->assertSessionHasErrors('waktu_validasi');
    }

    private function seedDatabase(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->assertSame(Command::SUCCESS, Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]));
    }
}
