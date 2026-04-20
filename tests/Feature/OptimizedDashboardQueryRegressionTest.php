<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OptimizedDashboardQueryRegressionTest extends TestCase
{
    private int $siswaAkunId;

    private int $pengurusAkunId;

    private int $waliAkunId;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->ensureSchema();

        $this->seedRoleAkun();
        $this->seedDashboardDataset();
    }

    private function ensureSchema(): void
    {
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
                $table->string('pembuat')->nullable();
            });
        }

        if (! Schema::hasTable('guru')) {
            Schema::create('guru', function (Blueprint $table): void {
                $table->id('id_guru');
                $table->unsignedBigInteger('id_akun')->nullable();
                $table->unsignedBigInteger('id_akun_guru')->nullable();
                $table->string('nama_guru');
                $table->string('foto_guru')->nullable();
                $table->string('pembuat')->nullable();
            });
        }

        if (! Schema::hasTable('kelas')) {
            Schema::create('kelas', function (Blueprint $table): void {
                $table->id('id_kelas');
                $table->unsignedBigInteger('id_jurusan')->nullable();
                $table->unsignedBigInteger('id_wali_kelas')->nullable();
                $table->unsignedBigInteger('id_guru_piket')->nullable();
                $table->unsignedBigInteger('id_guru_bk')->nullable();
                $table->string('tingkatan')->nullable();
                $table->string('nama_kelas');
                $table->string('status_kelas')->nullable();
                $table->string('pembuat')->nullable();
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
                $table->string('nomer_hp')->nullable();
                $table->string('jenis_kelamin')->nullable();
                $table->string('status_siswa')->nullable();
                $table->string('status_jabatan')->nullable();
                $table->integer('angkatan')->nullable();
                $table->string('foto_siswa')->nullable();
                $table->string('pembuat')->nullable();
            });
        }

        if (! Schema::hasTable('presensi_siswa')) {
            Schema::create('presensi_siswa', function (Blueprint $table): void {
                $table->id('id_presensi');
                $table->unsignedBigInteger('id_siswa');
                $table->string('foto_bukti')->nullable();
                $table->string('jam_masuk')->nullable();
                $table->date('tanggal')->nullable();
                $table->string('status_kehadiran');
                $table->text('keterangan')->nullable();
                $table->timestamps();
                $table->string('pembuat')->nullable();
            });
        }
    }

    public function test_siswa_dashboard_optimized_totals_match_baseline_query_output(): void
    {
        $expected = $this->baselineSiswaOrPengurusTotals($this->siswaAkunId);

        $response = $this->actingAs(Akun::findOrFail($this->siswaAkunId))->get('/siswa/dashboard');

        $response->assertOk()
            ->assertViewIs('siswa.index')
            ->assertViewHas('totalHadir', $expected['totalHadir'])
            ->assertViewHas('totalIzin', $expected['totalIzin'])
            ->assertViewHas('totalAlpha', $expected['totalAlpha']);
    }

    public function test_pengurus_dashboard_optimized_totals_match_baseline_query_output(): void
    {
        $expected = $this->baselineSiswaOrPengurusTotals($this->pengurusAkunId);

        $response = $this->actingAs(Akun::findOrFail($this->pengurusAkunId))->get('/pengurus-kelas/dashboard');

        $response->assertOk()
            ->assertViewIs('pengurus-kelas.index')
            ->assertViewHas('totalHadir', $expected['totalHadir'])
            ->assertViewHas('totalIzin', $expected['totalIzin'])
            ->assertViewHas('totalAlpha', $expected['totalAlpha']);
    }

    public function test_wali_kelas_dashboard_optimized_totals_match_baseline_query_output(): void
    {
        $expected = $this->baselineWaliTotals($this->waliAkunId);

        $response = $this->actingAs(Akun::findOrFail($this->waliAkunId))->get('/wali-kelas/dashboard');

        $response->assertOk()
            ->assertViewIs('wali-kelas.index')
            ->assertViewHas('totalSiswa', $expected['totalSiswa'])
            ->assertViewHas('totalHadir', $expected['totalHadir'])
            ->assertViewHas('totalIzin', $expected['totalIzin'])
            ->assertViewHas('totalAlpha', $expected['totalAlpha']);
    }

    public function test_optimized_query_paths_match_baseline_results(): void
    {
        $optimizedSiswaHadir = (int) PresensiSiswa::query()
            ->selectRaw('COUNT(*) as totalHadir')
            ->joinSiswaKelas()
            ->where('presensi_siswa.status_kehadiran', 'Hadir')
            ->where('siswa.id_akun', $this->siswaAkunId)
            ->value('totalHadir');

        $baselineSiswaHadir = (int) DB::table('presensi_siswa')
            ->join('siswa', 'presensi_siswa.id_siswa', '=', 'siswa.id_siswa')
            ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->where('presensi_siswa.status_kehadiran', 'Hadir')
            ->where('siswa.id_akun', $this->siswaAkunId)
            ->count();

        $optimizedWaliTotalSiswa = (int) Siswa::query()
            ->selectRaw('COUNT(*) as totalSiswa')
            ->joinKelasGuruWali()
            ->where('guru.id_akun', $this->waliAkunId)
            ->value('totalSiswa');

        $baselineWaliTotalSiswa = (int) DB::table('siswa')
            ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->join('guru', 'guru.id_guru', '=', 'kelas.id_wali_kelas')
            ->where('guru.id_akun', $this->waliAkunId)
            ->count();

        $this->assertSame($baselineSiswaHadir, $optimizedSiswaHadir);
        $this->assertSame($baselineWaliTotalSiswa, $optimizedWaliTotalSiswa);
    }

    private function baselineSiswaOrPengurusTotals(int $akunId): array
    {
        return [
            'totalHadir' => (int) DB::table('presensi_siswa')
                ->join('siswa', 'presensi_siswa.id_siswa', '=', 'siswa.id_siswa')
                ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
                ->where('presensi_siswa.status_kehadiran', 'Hadir')
                ->where('siswa.id_akun', $akunId)
                ->count(),
            'totalIzin' => (int) DB::table('presensi_siswa')
                ->join('siswa', 'presensi_siswa.id_siswa', '=', 'siswa.id_siswa')
                ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
                ->where('presensi_siswa.status_kehadiran', 'Izin')
                ->where('siswa.id_akun', $akunId)
                ->count(),
            'totalAlpha' => (int) DB::table('presensi_siswa')
                ->join('siswa', 'presensi_siswa.id_siswa', '=', 'siswa.id_siswa')
                ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
                ->where('presensi_siswa.status_kehadiran', 'Alpha')
                ->where('siswa.id_akun', $akunId)
                ->count(),
        ];
    }

    private function baselineWaliTotals(int $akunId): array
    {
        $attendance = DB::table('presensi_siswa')
            ->join('siswa', 'presensi_siswa.id_siswa', '=', 'siswa.id_siswa')
            ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->join('guru', 'guru.id_guru', '=', 'kelas.id_wali_kelas')
            ->where('guru.id_akun', $akunId)
            ->selectRaw("COALESCE(SUM(CASE WHEN presensi_siswa.status_kehadiran = 'Hadir' THEN 1 ELSE 0 END), 0) as totalHadir")
            ->selectRaw("COALESCE(SUM(CASE WHEN presensi_siswa.status_kehadiran = 'Izin' THEN 1 ELSE 0 END), 0) as totalIzin")
            ->selectRaw("COALESCE(SUM(CASE WHEN presensi_siswa.status_kehadiran = 'Alpha' THEN 1 ELSE 0 END), 0) as totalAlpha")
            ->first();

        return [
            'totalSiswa' => (int) DB::table('siswa')
                ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
                ->join('guru', 'guru.id_guru', '=', 'kelas.id_wali_kelas')
                ->where('guru.id_akun', $akunId)
                ->count(),
            'totalHadir' => (int) ($attendance->totalHadir ?? 0),
            'totalIzin' => (int) ($attendance->totalIzin ?? 0),
            'totalAlpha' => (int) ($attendance->totalAlpha ?? 0),
        ];
    }

    private function seedRoleAkun(): void
    {
        DB::table('role_akun')->insert([
            ['id_role' => 1, 'nama_role' => 'Siswa'],
            ['id_role' => 2, 'nama_role' => 'Wali Kelas'],
            ['id_role' => 3, 'nama_role' => 'Pengurus Kelas'],
            ['id_role' => 4, 'nama_role' => 'Guru Piket'],
            ['id_role' => 5, 'nama_role' => 'Guru BK'],
            ['id_role' => 6, 'nama_role' => 'Tata Usaha'],
        ]);
    }

    private function seedDashboardDataset(): void
    {
        DB::table('jurusan')->insert([
            'id_jurusan' => 1,
            'nama_jurusan' => 'RPL',
            'pembuat' => 'Seeder',
        ]);

        $this->waliAkunId = (int) DB::table('akun')->insertGetId([
            'id_role' => 2,
            'username' => 'wali',
            'password' => bcrypt('secret'),
        ], 'id_akun');

        $waliGuruId = (int) DB::table('guru')->insertGetId([
            'id_akun' => $this->waliAkunId,
            'nama_guru' => 'Wali Kelas',
            'foto_guru' => 'guru.jpg',
            'pembuat' => 'Seeder',
        ], 'id_guru');

        $kelasId = (int) DB::table('kelas')->insertGetId([
            'id_jurusan' => 1,
            'id_wali_kelas' => $waliGuruId,
            'tingkatan' => 'X',
            'nama_kelas' => 'RPL 1',
            'status_kelas' => 'aktif',
            'pembuat' => 'Seeder',
        ], 'id_kelas');

        $this->siswaAkunId = (int) DB::table('akun')->insertGetId([
            'id_role' => 1,
            'username' => 'siswa',
            'password' => bcrypt('secret'),
        ], 'id_akun');

        $siswaId = (int) DB::table('siswa')->insertGetId([
            'id_akun' => $this->siswaAkunId,
            'id_kelas' => $kelasId,
            'nis' => '1001',
            'nama_siswa' => 'Siswa Satu',
            'nomer_hp' => '081234567890',
            'jenis_kelamin' => 'laki-laki',
            'status_siswa' => 'aktif',
            'status_jabatan' => 'siswa',
            'angkatan' => 2026,
            'foto_siswa' => 'siswa.jpg',
            'pembuat' => 'Seeder',
        ], 'id_siswa');

        $this->pengurusAkunId = (int) DB::table('akun')->insertGetId([
            'id_role' => 3,
            'username' => 'pengurus',
            'password' => bcrypt('secret'),
        ], 'id_akun');

        $pengurusSiswaId = (int) DB::table('siswa')->insertGetId([
            'id_akun' => $this->pengurusAkunId,
            'id_kelas' => $kelasId,
            'nis' => '1002',
            'nama_siswa' => 'Siswa Dua',
            'nomer_hp' => '081234567891',
            'jenis_kelamin' => 'laki-laki',
            'status_siswa' => 'aktif',
            'status_jabatan' => 'ketua_kelas',
            'angkatan' => 2026,
            'foto_siswa' => 'siswa.jpg',
            'pembuat' => 'Seeder',
        ], 'id_siswa');

        DB::table('presensi_siswa')->insert([
            [
                'id_siswa' => $siswaId,
                'foto_bukti' => 'bukti1.jpg',
                'jam_masuk' => '07:00:00',
                'tanggal' => '2026-01-10',
                'status_kehadiran' => 'Hadir',
                'keterangan' => 'ok',
                'created_at' => now(),
                'updated_at' => now(),
                'pembuat' => 'Seeder',
            ],
            [
                'id_siswa' => $siswaId,
                'foto_bukti' => 'bukti2.jpg',
                'jam_masuk' => '07:01:00',
                'tanggal' => '2026-01-11',
                'status_kehadiran' => 'Izin',
                'keterangan' => 'ok',
                'created_at' => now(),
                'updated_at' => now(),
                'pembuat' => 'Seeder',
            ],
            [
                'id_siswa' => $siswaId,
                'foto_bukti' => 'bukti3.jpg',
                'jam_masuk' => '07:02:00',
                'tanggal' => '2026-01-12',
                'status_kehadiran' => 'Alpha',
                'keterangan' => 'ok',
                'created_at' => now(),
                'updated_at' => now(),
                'pembuat' => 'Seeder',
            ],
            [
                'id_siswa' => $siswaId,
                'foto_bukti' => 'bukti4.jpg',
                'jam_masuk' => '07:03:00',
                'tanggal' => '2026-01-13',
                'status_kehadiran' => 'Hadir',
                'keterangan' => 'ok',
                'created_at' => now(),
                'updated_at' => now(),
                'pembuat' => 'Seeder',
            ],
            [
                'id_siswa' => $pengurusSiswaId,
                'foto_bukti' => 'bukti5.jpg',
                'jam_masuk' => '07:00:00',
                'tanggal' => '2026-01-10',
                'status_kehadiran' => 'Hadir',
                'keterangan' => 'ok',
                'created_at' => now(),
                'updated_at' => now(),
                'pembuat' => 'Seeder',
            ],
            [
                'id_siswa' => $pengurusSiswaId,
                'foto_bukti' => 'bukti6.jpg',
                'jam_masuk' => '07:01:00',
                'tanggal' => '2026-01-11',
                'status_kehadiran' => 'Alpha',
                'keterangan' => 'ok',
                'created_at' => now(),
                'updated_at' => now(),
                'pembuat' => 'Seeder',
            ],
        ]);

        $this->assertDatabaseCount('akun', 3);
        $this->assertDatabaseCount('siswa', 2);
        $this->assertDatabaseCount('presensi_siswa', 6);
    }
}
