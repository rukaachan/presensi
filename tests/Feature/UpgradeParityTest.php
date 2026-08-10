<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UpgradeParityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->ensureSchema();
        $this->seedReferenceData();
    }

    public function test_unauthenticated_user_sees_login_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_authenticated_user_redirected_to_dashboard(): void
    {
        $response = $this->post('/', [
            'username' => 'tu',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/tata-usaha/dashboard');
    }

    public function test_logout_redirects_to_login(): void
    {
        $this->actingAs($this->getAkunByUsername('tu'));

        $response = $this->post('/logout');

        $response->assertRedirect('/');
    }

    public function test_invalid_login_redirects_back(): void
    {
        $response = $this->from('/')->post('/', [
            'username' => 'tu',
            'password' => 'wrong-pass',
        ]);

        $response->assertRedirect('/');
    }

    public function test_role_middleware_blocks_wrong_role(): void
    {
        $this->actingAs($this->getAkunByUsername('siswa'));

        $response = $this->get('/tata-usaha/dashboard');

        $response->assertRedirect('/');
    }

    public function test_role_middleware_allows_correct_role(): void
    {
        $this->actingAs($this->getAkunByUsername('tu'));

        $response = $this->get('/tata-usaha/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('tata-usaha.index');
    }

    public function test_tata_usaha_dashboard_returns_view(): void
    {
        $this->actingAs($this->getAkunByUsername('tu'));

        $response = $this->get('/tata-usaha/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('tata-usaha.index');
    }

    public function test_wali_kelas_dashboard_returns_view_with_totals(): void
    {
        $this->actingAs($this->getAkunByUsername('wali'));

        $response = $this->get('/wali-kelas/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('wali-kelas.index');
        $response->assertViewHas('totalHadir');
        $response->assertViewHas('totalIzin');
        $response->assertViewHas('totalAlpha');
        $response->assertViewHas('totalSiswa');
    }

    public function test_siswa_dashboard_returns_view(): void
    {
        $this->actingAs($this->getAkunByUsername('siswa'));

        $response = $this->get('/siswa/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('siswa.index');
    }

    public function test_json_endpoint_returns_expected_shape(): void
    {
        $this->actingAs($this->getAkunByUsername('siswa'));

        $response = $this->post('/siswa/webcam/check_snapshot', [
            'id_siswa' => 1,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['exists']);
    }

    public function test_pdf_endpoint_returns_pdf_response(): void
    {
        $this->actingAs($this->getAkunByUsername('bk'));

        $response = $this->get('/guru-bk/presensi-pdf');

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('content-type')
        );
    }

    public function test_session_token_regenerated_on_login(): void
    {
        $this->startSession();
        $before = session()->token();

        $response = $this->post('/', [
            'username' => 'tu',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/tata-usaha/dashboard');
        $this->assertNotSame($before, session()->token());
    }

    private function ensureSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        if (! Schema::hasTable('role_akun')) {
            Schema::create('role_akun', function (Blueprint $table): void {
                $table->id('id_role');
                $table->string('nama_role');
            });
        }

        if (! Schema::hasTable('akun')) {
            Schema::create('akun', function (Blueprint $table): void {
                $table->id('id_akun');
                $table->unsignedBigInteger('id_role');
                $table->string('username');
                $table->string('password');
                $table->string('foto')->nullable();
            });
        }

        if (! Schema::hasTable('jurusan')) {
            Schema::create('jurusan', function (Blueprint $table): void {
                $table->id('id_jurusan');
                $table->string('nama_jurusan');
                $table->string('kode_jurusan')->nullable();
            });
        }

        if (! Schema::hasTable('guru')) {
            Schema::create('guru', function (Blueprint $table): void {
                $table->id('id_guru');
                $table->unsignedBigInteger('id_akun')->nullable();
                $table->unsignedBigInteger('id_akun_guru')->nullable();
                $table->string('nama_guru');
            });
        }

        if (! Schema::hasTable('kelas')) {
            Schema::create('kelas', function (Blueprint $table): void {
                $table->id('id_kelas');
                $table->unsignedBigInteger('id_jurusan')->nullable();
                $table->unsignedBigInteger('id_wali_kelas')->nullable();
                $table->unsignedBigInteger('id_guru_piket')->nullable();
                $table->unsignedBigInteger('id_guru_bk')->nullable();
                $table->string('nama_kelas');
                $table->integer('tingkatan')->nullable();
                $table->string('status_kelas')->default('aktif');
            });
        }

        if (! Schema::hasTable('guru_bk')) {
            Schema::create('guru_bk', function (Blueprint $table): void {
                $table->id('id_guru_bk');
                $table->unsignedBigInteger('id_akun_guru_bk')->nullable();
                $table->unsignedBigInteger('id_kelas')->nullable();
                $table->string('nama_guru_bk')->nullable();
            });
        }

        if (! Schema::hasTable('guru_piket')) {
            Schema::create('guru_piket', function (Blueprint $table): void {
                $table->id('id_guru_piket');
                $table->unsignedBigInteger('id_akun_guru_piket')->nullable();
                $table->unsignedBigInteger('id_kelas')->nullable();
                $table->string('nama_guru_piket')->nullable();
            });
        }

        if (! Schema::hasTable('pengurus_kelas')) {
            Schema::create('pengurus_kelas', function (Blueprint $table): void {
                $table->id('id_pengurus');
                $table->unsignedBigInteger('id_siswa');
            });
        }

        if (! Schema::hasTable('siswa')) {
            Schema::create('siswa', function (Blueprint $table): void {
                $table->id('id_siswa');
                $table->unsignedBigInteger('id_akun')->nullable();
                $table->unsignedBigInteger('id_akun_siswa')->nullable();
                $table->unsignedBigInteger('id_akun_pengurus')->nullable();
                $table->unsignedBigInteger('id_kelas')->nullable();
                $table->string('nis')->nullable();
                $table->string('nama_siswa');
                $table->string('status_siswa')->default('aktif');
            });
        }

        if (! Schema::hasTable('presensi_siswa')) {
            Schema::create('presensi_siswa', function (Blueprint $table): void {
                $table->id('id_presensi');
                $table->unsignedBigInteger('id_siswa');
                $table->date('tanggal')->nullable();
                $table->string('status_kehadiran');
                $table->string('foto')->nullable();
                $table->string('foto_bukti')->nullable();
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('validasi')) {
            Schema::create('validasi', function (Blueprint $table): void {
                $table->id('id_validasi');
                $table->unsignedBigInteger('id_presensi');
                $table->string('status_validasi')->default('tidak_ada');
            });
        }

        if (! Schema::hasTable('logs')) {
            Schema::create('logs', function (Blueprint $table): void {
                $table->id('id_logs');
                $table->unsignedBigInteger('id_akun')->nullable();
                $table->string('aktifitas')->nullable();
                $table->string('aktor')->nullable();
                $table->string('aksi')->nullable();
                $table->string('record')->nullable();
                $table->date('tanggal')->nullable();
                $table->time('jam')->nullable();
                $table->string('status')->default('aktif');
                $table->timestamps();
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    private function seedReferenceData(): void
    {
        DB::table('role_akun')->insert([
            ['id_role' => 1, 'nama_role' => 'Siswa'],
            ['id_role' => 2, 'nama_role' => 'Wali Kelas'],
            ['id_role' => 3, 'nama_role' => 'Pengurus Kelas'],
            ['id_role' => 4, 'nama_role' => 'Guru Piket'],
            ['id_role' => 5, 'nama_role' => 'Guru BK'],
            ['id_role' => 6, 'nama_role' => 'Tata Usaha'],
        ]);

        DB::table('akun')->insert([
            ['id_akun' => 1, 'id_role' => 6, 'username' => 'tu', 'password' => Hash::make('password123')],
            ['id_akun' => 2, 'id_role' => 2, 'username' => 'wali', 'password' => Hash::make('password123')],
            ['id_akun' => 3, 'id_role' => 1, 'username' => 'siswa', 'password' => Hash::make('password123')],
            ['id_akun' => 4, 'id_role' => 5, 'username' => 'bk', 'password' => Hash::make('password123')],
        ]);

        DB::table('jurusan')->insert([
            'id_jurusan' => 1,
            'nama_jurusan' => 'Teknik Informatika',
            'kode_jurusan' => 'TI',
        ]);

        DB::table('kelas')->insert([
            'id_kelas' => 1,
            'id_jurusan' => 1,
            'id_wali_kelas' => 1,
            'nama_kelas' => 'XII-RPL-1',
            'tingkatan' => 12,
        ]);

        DB::table('guru')->insert([
            'id_guru' => 1,
            'id_akun' => 2,
            'id_akun_guru' => 2,
            'nama_guru' => 'Guru Wali',
        ]);

        DB::table('siswa')->insert([
            'id_siswa' => 1,
            'id_akun' => 3,
            'id_akun_siswa' => 3,
            'id_kelas' => 1,
            'nis' => '123456',
            'nama_siswa' => 'Siswa Uji',
        ]);

        DB::table('presensi_siswa')->insert([
            'id_presensi' => 1,
            'id_siswa' => 1,
            'tanggal' => now()->toDateString(),
            'status_kehadiran' => 'Hadir',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getAkunByUsername(string $username): Akun
    {
        return Akun::query()->where('username', $username)->firstOrFail();
    }
}
