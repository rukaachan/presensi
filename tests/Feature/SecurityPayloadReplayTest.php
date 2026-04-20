<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SecurityPayloadReplayTest extends TestCase
{
    private int $tataUsahaAkunId;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->ensureSchema();

        $this->seedRoleAkun();
        $this->seedDataset();
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

        if (! Schema::hasTable('logs')) {
            Schema::create('logs', function (Blueprint $table): void {
                $table->id('id_logs');
                $table->string('tabel')->nullable();
                $table->string('aktor')->nullable();
                $table->date('tanggal')->nullable();
                $table->string('jam')->nullable();
                $table->string('aksi')->nullable();
                $table->text('record')->nullable();
                $table->string('status')->nullable();
                $table->unsignedBigInteger('id_akun')->nullable();
                $table->string('aktifitas')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_siswa_query_endpoint_rejects_sql_injection_payload_replay(): void
    {
        $payload = "' OR 1=1 --";

        $response = $this->actingAs(Akun::findOrFail($this->tataUsahaAkunId))
            ->get('/tata-usaha/akun-siswa?keyword='.urlencode($payload).'&filter_status=aktif');

        $response->assertOk()
            ->assertViewIs('tata-usaha.siswa')
            ->assertViewHas('siswa', fn ($rows) => $rows->count() === 0)
            ->assertDontSee('SQLSTATE');
    }

    public function test_presensi_query_endpoint_rejects_sql_injection_payload_replay(): void
    {
        $payload = "' OR 1=1 --";

        $response = $this->actingAs(Akun::findOrFail($this->tataUsahaAkunId))
            ->get('/tata-usaha/presensi?keyword='.urlencode($payload).'&filter_kehadiran=hadir');

        $response->assertOk()
            ->assertViewIs('tata-usaha.presensi')
            ->assertViewHas('presensi', fn ($rows) => $rows->count() === 0)
            ->assertDontSee('SQLSTATE');
    }

    public function test_logs_query_endpoint_rejects_sql_injection_payload_replay(): void
    {
        $payload = "' OR 1=1 --";

        $response = $this->actingAs(Akun::findOrFail($this->tataUsahaAkunId))
            ->get('/tata-usaha/logs?keyword='.urlencode($payload));

        $response->assertOk()
            ->assertViewIs('tata-usaha.logs')
            ->assertViewHas('logs', fn ($rows) => $rows->count() === 0)
            ->assertDontSee('SQLSTATE');
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

    private function seedDataset(): void
    {
        $this->tataUsahaAkunId = (int) DB::table('akun')->insertGetId([
            'id_role' => 6,
            'username' => 'tu-admin',
            'password' => bcrypt('secret'),
        ], 'id_akun');

        $siswaAkunId = (int) DB::table('akun')->insertGetId([
            'id_role' => 1,
            'username' => 'siswa-test',
            'password' => bcrypt('secret'),
        ], 'id_akun');

        DB::table('jurusan')->insert([
            'id_jurusan' => 1,
            'nama_jurusan' => 'RPL',
            'pembuat' => 'Seeder',
        ]);

        DB::table('kelas')->insert([
            'id_kelas' => 1,
            'id_jurusan' => 1,
            'id_wali_kelas' => null,
            'tingkatan' => 'X',
            'nama_kelas' => 'RPL 1',
            'status_kelas' => 'aktif',
            'pembuat' => 'Seeder',
        ]);

        DB::table('siswa')->insert([
            'id_siswa' => 1,
            'id_akun' => $siswaAkunId,
            'id_kelas' => 1,
            'nis' => 1001,
            'nama_siswa' => 'Siswa Aman',
            'nomer_hp' => '081234567890',
            'jenis_kelamin' => 'laki-laki',
            'status_siswa' => 'aktif',
            'status_jabatan' => 'siswa',
            'angkatan' => 2026,
            'foto_siswa' => 'avatar.jpg',
            'pembuat' => 'Seeder',
        ]);

        DB::table('presensi_siswa')->insert([
            'id_presensi' => 1,
            'id_siswa' => 1,
            'foto_bukti' => 'bukti.jpg',
            'jam_masuk' => '07:00:00',
            'tanggal' => '2026-01-10',
            'status_kehadiran' => 'hadir',
            'keterangan' => 'tepat waktu',
            'created_at' => now(),
            'updated_at' => now(),
            'pembuat' => 'Wali Kelas',
        ]);

        DB::table('logs')->insert([
            [
                'tabel' => 'siswa',
                'aktor' => 'Tata Usaha',
                'tanggal' => '2026-01-10',
                'jam' => '08:00:00',
                'aksi' => 'Tambah',
                'record' => 'insert siswa',
                'status' => 'aktif',
            ],
            [
                'tabel' => 'siswa',
                'aktor' => 'Tata Usaha',
                'tanggal' => '2026-01-11',
                'jam' => '08:05:00',
                'aksi' => 'Edit',
                'record' => 'update siswa',
                'status' => 'tidak_aktif',
            ],
        ]);

        $this->assertDatabaseHas('akun', ['id_akun' => $this->tataUsahaAkunId, 'id_role' => 6]);
        $this->assertDatabaseHas('logs', ['tabel' => 'siswa', 'aktor' => 'Tata Usaha', 'aksi' => 'Tambah']);
    }
}
