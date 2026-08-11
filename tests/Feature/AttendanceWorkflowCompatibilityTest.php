<?php

namespace Tests\Feature;

use App\Domain\Attendance\AttendanceState;
use App\Models\Akun;
use App\Models\AttendanceRecord;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceWorkflowCompatibilityTest extends TestCase
{
    public function test_student_capture_dual_writes_legacy_read_model_and_private_target_record(): void
    {
        $this->seedDatabase();
        Storage::fake('local');

        $account = $this->account('siswa.demo');
        $student = $this->studentFor($account);
        $this->removeLegacyToday($student);

        $response = $this->actingAs($account)->post(route('siswa.webcam.capture'), [
            'image' => $this->pngDataUri(),
        ]);

        $response->assertRedirect();
        $legacy = PresensiSiswa::query()
            ->where('id_siswa', $student->getKey())
            ->whereDate('tanggal', now(config('attendance.timezone'))->toDateString())
            ->latest('id_presensi')
            ->firstOrFail();
        $target = AttendanceRecord::query()->where('legacy_presensi_id', $legacy->getKey())->firstOrFail();

        $this->assertSame('', $legacy->foto_bukti);
        $this->assertSame(AttendanceState::SUBMITTED, $target->state);
        $this->assertSame(3, DB::table('validasi')->where('id_presensi', $legacy->getKey())->count());
        $this->assertNotNull($target->evidence_path);
        Storage::disk('local')->assertExists($target->evidence_path);
        $this->assertFalse(is_file(public_path('presensi_bukti/'.$legacy->foto_bukti)));
        $this->actingAs($account)
            ->get(route('attendance.evidence', $target))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_invalid_capture_leaves_no_private_file_or_legacy_row(): void
    {
        $this->seedDatabase();
        Storage::fake('local');

        $account = $this->account('siswa.demo');
        $student = $this->studentFor($account);
        $this->removeLegacyToday($student);

        $response = $this->actingAs($account)->post(route('siswa.webcam.capture'), [
            'image' => 'data:image/png;base64,not-valid-base64',
        ]);

        $response->assertRedirect()->assertSessionHas('error');
        $this->assertSame(0, AttendanceRecord::query()->count());
        $this->assertSame(0, PresensiSiswa::query()
            ->where('id_siswa', $student->getKey())
            ->whereDate('tanggal', now(config('attendance.timezone'))->toDateString())
            ->count());
        $this->assertSame([], Storage::disk('local')->allFiles('attendance/evidence'));
    }

    public function test_class_officer_cannot_capture_for_another_class_and_upload_is_cleaned_up(): void
    {
        $this->seedDatabase();
        Storage::fake('local');

        $account = $this->account('pengurus.demo');
        $officer = $this->studentFor($account);
        $target = Siswa::query()
            ->where('id_kelas', '!=', $officer->id_kelas)
            ->firstOrFail();
        $this->removeLegacyToday($target);

        $response = $this->actingAs($account)->post(route('pengurus-kelas.webcam.capture'), [
            'id_siswa' => $target->getKey(),
            'image' => $this->pngDataUri(),
        ]);

        $response->assertRedirect()->assertSessionHas('error');
        $this->assertSame(0, AttendanceRecord::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles('attendance/evidence'));
    }

    public function test_class_officer_capture_keeps_class_scope_and_legacy_route_contract(): void
    {
        $this->seedDatabase();
        Storage::fake('local');

        $account = $this->account('pengurus.demo');
        $officer = $this->studentFor($account);
        $target = Siswa::query()
            ->where('id_kelas', $officer->id_kelas)
            ->where('id_siswa', '!=', $officer->getKey())
            ->firstOrFail();
        $this->removeLegacyToday($target);

        $response = $this->actingAs($account)->post(route('pengurus-kelas.webcam.capture'), [
            'id_siswa' => $target->getKey(),
            'image' => $this->pngDataUri(),
        ]);

        $response->assertRedirect();
        $legacy = PresensiSiswa::query()
            ->where('id_siswa', $target->getKey())
            ->whereDate('tanggal', now(config('attendance.timezone'))->toDateString())
            ->latest('id_presensi')
            ->firstOrFail();
        $this->assertDatabaseHas('attendance_records', [
            'legacy_presensi_id' => $legacy->getKey(),
            'source' => 'class_officer',
        ]);
    }

    public function test_duty_teacher_update_is_audited_and_updates_the_target_state(): void
    {
        $this->seedDatabase();
        $account = $this->account('siswa.demo');
        $student = $this->studentFor($account);
        $legacy = PresensiSiswa::query()
            ->where('id_siswa', $student->getKey())
            ->whereDate('tanggal', now(config('attendance.timezone'))->toDateString())
            ->firstOrFail();
        $target = AttendanceRecord::query()->create([
            'student_id' => $student->getKey(),
            'attendance_session_id' => 1,
            'attendance_date' => $legacy->getRawOriginal('tanggal'),
            'state' => AttendanceState::SUBMITTED,
            'source' => 'legacy',
            'legacy_presensi_id' => $legacy->getKey(),
            'idempotency_key' => 'workflow-test-'.$legacy->getKey(),
        ]);

        $response = $this->actingAs($this->account('piket.demo'))->post(route('guru-piket.presensi.update'), [
            'id_presensi' => $legacy->getKey(),
            'status_kehadiran' => 'izin',
            'keterangan' => 'Surat keterangan diterima.',
        ]);

        $response->assertRedirect();
        $this->assertSame(AttendanceState::EXCUSED, $target->refresh()->state);
        $this->assertDatabaseHas('audit_events', ['action' => 'attendance.corrected']);
    }

    private function seedDatabase(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->assertSame(Command::SUCCESS, Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]));
        DB::table('attendance_events')->delete();
        DB::table('attendance_records')->delete();
        DB::table('audit_events')->delete();
    }

    private function account(string $username): Akun
    {
        return Akun::query()->where('username', $username)->firstOrFail();
    }

    private function studentFor(Akun $account): Siswa
    {
        return Siswa::query()->where('id_akun', $account->getKey())->firstOrFail();
    }

    private function removeLegacyToday(Siswa $student): void
    {
        PresensiSiswa::query()
            ->where('id_siswa', $student->getKey())
            ->whereDate('tanggal', now(config('attendance.timezone'))->toDateString())
            ->delete();
    }

    private function pngDataUri(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    }
}
