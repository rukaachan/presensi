<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PresensiJoinIntegrityTest extends TestCase
{
    private int $guruBkAkunId;

    private int $guruPiketAkunId;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->ensureSchema();

        DB::table('role_akun')->insert([
            ['id_role' => 4, 'nama_role' => 'Guru Piket'],
            ['id_role' => 5, 'nama_role' => 'Guru BK'],
            ['id_role' => 1, 'nama_role' => 'Siswa'],
        ]);

        $this->guruBkAkunId = (int) DB::table('akun')->insertGetId([
            'id_role' => 5,
            'username' => 'gubk',
            'password' => bcrypt('secret'),
        ], 'id_akun');

        $this->guruPiketAkunId = (int) DB::table('akun')->insertGetId([
            'id_role' => 4,
            'username' => 'gupiket',
            'password' => bcrypt('secret'),
        ], 'id_akun');

        DB::table('guru')->insert([
            [
                'id_akun' => $this->guruBkAkunId,
                'nama_guru' => 'Guru BK Test',
                'foto_guru' => 'guru-bk.jpg',
                'pembuat' => 'Seeder',
            ],
            [
                'id_akun' => $this->guruPiketAkunId,
                'nama_guru' => 'Guru Piket Test',
                'foto_guru' => 'guru-piket.jpg',
                'pembuat' => 'Seeder',
            ],
        ]);

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

        $siswaAkunId = (int) DB::table('akun')->insertGetId([
            'id_role' => 1,
            'username' => 'siswa-join-test',
            'password' => bcrypt('secret'),
        ], 'id_akun');

        DB::table('siswa')->insert([
            'id_siswa' => 1,
            'id_akun' => $siswaAkunId,
            'id_kelas' => 1,
            'nis' => 1001,
            'nama_siswa' => 'Siswa Join Test',
            'nomer_hp' => '081234567890',
            'jenis_kelamin' => 'laki-laki',
            'status_siswa' => 'aktif',
            'status_jabatan' => 'siswa',
            'angkatan' => 2026,
            'foto_siswa' => 'siswa.jpg',
            'pembuat' => 'Seeder',
        ]);

        DB::table('presensi_siswa')->insert([
            'id_presensi' => 99,
            'id_siswa' => 1,
            'foto_bukti' => 'bukti.jpg',
            'jam_masuk' => '07:00:00',
            'tanggal' => '2026-02-20',
            'status_kehadiran' => 'Hadir',
            'keterangan' => 'tepat waktu',
            'created_at' => now(),
            'updated_at' => now(),
            'pembuat' => 'Seeder',
        ]);
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
    }

    public function test_guru_bk_presensi_list_uses_id_siswa_join_integrity(): void
    {
        $response = $this->actingAs(Akun::findOrFail($this->guruBkAkunId))
            ->get('/guru-bk/presensi?keyword=Siswa+Join+Test&filter_kehadiran=Hadir');

        $response->assertOk()
            ->assertViewIs('guru-bk.presensi')
            ->assertViewHas('presensi', fn ($rows) => $rows->count() === 1);
    }

    public function test_guru_piket_presensi_list_uses_id_siswa_join_integrity(): void
    {
        $response = $this->actingAs(Akun::findOrFail($this->guruPiketAkunId))
            ->get('/guru-piket/presensi?keyword=Siswa+Join+Test&filter_kehadiran=Hadir');

        $response->assertOk()
            ->assertViewIs('guru-piket.presensi')
            ->assertViewHas('presensi', fn ($rows) => $rows->count() === 1);
    }
}
