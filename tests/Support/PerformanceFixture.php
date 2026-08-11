<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class PerformanceFixture
{
    public static function createSchema(): void
    {
        Schema::create('role_akun', function (Blueprint $table): void {
            $table->id('id_role');
            $table->string('nama_role');
        });

        Schema::create('akun', function (Blueprint $table): void {
            $table->id('id_akun');
            $table->unsignedBigInteger('id_role');
            $table->string('username');
            $table->string('password');
            $table->string('foto')->nullable();
        });

        Schema::create('jurusan', function (Blueprint $table): void {
            $table->id('id_jurusan');
            $table->string('nama_jurusan');
            $table->string('kode_jurusan')->nullable();
            $table->string('pembuat')->nullable();
        });

        Schema::create('guru', function (Blueprint $table): void {
            $table->id('id_guru');
            $table->unsignedBigInteger('id_akun');
            $table->string('nama_guru');
            $table->string('foto_guru');
            $table->string('pembuat')->nullable();
        });

        Schema::create('guru_bk', function (Blueprint $table): void {
            $table->id('id_bk');
            $table->unsignedBigInteger('id_guru');
        });

        Schema::create('guru_piket', function (Blueprint $table): void {
            $table->id('id_piket');
            $table->unsignedBigInteger('id_guru');
        });

        Schema::create('tata_usaha', function (Blueprint $table): void {
            $table->id('id_tata_usaha');
            $table->unsignedBigInteger('id_akun');
        });

        Schema::create('kelas', function (Blueprint $table): void {
            $table->id('id_kelas');
            $table->unsignedBigInteger('id_jurusan');
            $table->unsignedBigInteger('id_wali_kelas')->nullable();
            $table->unsignedBigInteger('id_guru_piket')->nullable();
            $table->unsignedBigInteger('id_guru_bk')->nullable();
            $table->string('tingkatan');
            $table->string('nama_kelas');
            $table->string('status_kelas');
            $table->string('pembuat')->nullable();
        });

        Schema::create('siswa', function (Blueprint $table): void {
            $table->id('id_siswa');
            $table->unsignedBigInteger('id_akun');
            $table->unsignedBigInteger('id_kelas');
            $table->string('nis');
            $table->string('nama_siswa');
            $table->string('nomer_hp')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->string('status_siswa')->nullable();
            $table->string('status_jabatan')->nullable();
            $table->integer('angkatan')->nullable();
            $table->string('foto_siswa')->nullable();
            $table->string('pembuat')->nullable();
        });

        Schema::create('pengurus_kelas', function (Blueprint $table): void {
            $table->id('id_pengurus');
            $table->unsignedBigInteger('id_siswa');
            $table->string('jabatan');
            $table->string('pembuat')->nullable();
        });

        Schema::create('presensi_siswa', function (Blueprint $table): void {
            $table->id('id_presensi');
            $table->unsignedBigInteger('id_siswa');
            $table->string('foto_bukti')->nullable();
            $table->string('jam_masuk')->nullable();
            $table->date('tanggal');
            $table->string('status_kehadiran');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->string('pembuat')->nullable();
            $table->index(['id_siswa', 'status_kehadiran']);
        });

        Schema::create('validasi', function (Blueprint $table): void {
            $table->id('id_validasi');
            $table->unsignedBigInteger('id_pengurus')->nullable();
            $table->unsignedBigInteger('id_presensi');
            $table->string('status_validasi');
            $table->string('waktu_validasi');
        });

        Schema::create('attendance_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->string('kind');
            $table->string('legacy_code')->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('active')->default(true);
            $table->time('window_start')->nullable();
            $table->time('window_end')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('logs', function (Blueprint $table): void {
            $table->id('id_log');
            $table->string('tabel')->nullable();
            $table->string('aktor')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('jam')->nullable();
            $table->string('aksi')->nullable();
            $table->text('record')->nullable();
            $table->string('status')->nullable();
        });

        DB::statement('
            CREATE VIEW view_siswa AS
            SELECT siswa.*, kelas.tingkatan, kelas.nama_kelas, jurusan.nama_jurusan
            FROM siswa
            JOIN kelas ON siswa.id_kelas = kelas.id_kelas
            JOIN jurusan ON kelas.id_jurusan = jurusan.id_jurusan
        ');

        DB::statement('
            CREATE VIEW view_presensi AS
            SELECT presensi_siswa.*, siswa.nama_siswa, kelas.nama_kelas, kelas.tingkatan, jurusan.nama_jurusan
            FROM presensi_siswa
            JOIN siswa ON presensi_siswa.id_siswa = siswa.id_siswa
            JOIN kelas ON siswa.id_kelas = kelas.id_kelas
            JOIN jurusan ON kelas.id_jurusan = jurusan.id_jurusan
        ');
    }

    public static function seed(): array
    {
        DB::table('role_akun')->insert([
            ['id_role' => 1, 'nama_role' => 'Siswa'],
            ['id_role' => 2, 'nama_role' => 'Wali Kelas'],
            ['id_role' => 3, 'nama_role' => 'Pengurus Kelas'],
            ['id_role' => 4, 'nama_role' => 'Guru Piket'],
            ['id_role' => 5, 'nama_role' => 'Guru BK'],
            ['id_role' => 6, 'nama_role' => 'Tata Usaha'],
        ]);

        DB::table('attendance_sessions')->insert([
            [
                'code' => 'daily_check_in',
                'label' => 'Check-in harian',
                'kind' => 'check_in',
                'legacy_code' => null,
                'required' => true,
                'active' => true,
                'sort_order' => 10,
                'settings' => json_encode(['evidence' => 'photo']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'break_1',
                'label' => 'Istirahat pertama',
                'kind' => 'break',
                'legacy_code' => 'istirahat_pertama',
                'required' => false,
                'active' => true,
                'sort_order' => 20,
                'settings' => json_encode(['evidence' => 'optional']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'break_2',
                'label' => 'Istirahat kedua',
                'kind' => 'break',
                'legacy_code' => 'istirahat_kedua',
                'required' => false,
                'active' => true,
                'sort_order' => 30,
                'settings' => json_encode(['evidence' => 'optional']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'break_3',
                'label' => 'Istirahat ketiga',
                'kind' => 'break',
                'legacy_code' => 'istirahat_ketiga',
                'required' => false,
                'active' => true,
                'sort_order' => 40,
                'settings' => json_encode(['evidence' => 'optional']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $accounts = [];
        foreach ([
            'siswa' => 1,
            'wali' => 2,
            'pengurus' => 3,
            'guru_piket' => 4,
            'guru_bk' => 5,
            'tata_usaha' => 6,
        ] as $name => $roleId) {
            $accounts[$name] = (int) DB::table('akun')->insertGetId([
                'id_role' => $roleId,
                'username' => $name,
                'password' => Hash::make('password'),
            ], 'id_akun');
        }

        DB::table('jurusan')->insert([
            'id_jurusan' => 1,
            'nama_jurusan' => 'Rekayasa Perangkat Lunak',
            'kode_jurusan' => 'RPL',
            'pembuat' => 'Fixture',
        ]);

        $guruIds = [];
        foreach (['wali', 'guru_piket', 'guru_bk'] as $role) {
            $guruIds[$role] = (int) DB::table('guru')->insertGetId([
                'id_akun' => $accounts[$role],
                'nama_guru' => str_replace('_', ' ', $role),
                'foto_guru' => 'guru.jpg',
                'pembuat' => 'Fixture',
            ], 'id_guru');
        }

        DB::table('guru_piket')->insert(['id_guru' => $guruIds['guru_piket']]);
        DB::table('guru_bk')->insert(['id_guru' => $guruIds['guru_bk']]);
        DB::table('tata_usaha')->insert(['id_akun' => $accounts['tata_usaha']]);

        $kelasId = (int) DB::table('kelas')->insertGetId([
            'id_jurusan' => 1,
            'id_wali_kelas' => $guruIds['wali'],
            'id_guru_piket' => $guruIds['guru_piket'],
            'id_guru_bk' => $guruIds['guru_bk'],
            'tingkatan' => 'XII',
            'nama_kelas' => 'RPL 1',
            'status_kelas' => 'aktif',
            'pembuat' => 'Fixture',
        ], 'id_kelas');

        $studentIds = [];
        foreach (['siswa', 'pengurus'] as $index => $role) {
            $studentIds[$role] = (int) DB::table('siswa')->insertGetId([
                'id_akun' => $accounts[$role],
                'id_kelas' => $kelasId,
                'nis' => '100'.($index + 1),
                'nama_siswa' => ucfirst($role),
                'nomer_hp' => '08000000000',
                'jenis_kelamin' => 'laki-laki',
                'status_siswa' => 'aktif',
                'status_jabatan' => $role === 'pengurus' ? 'ketua_kelas' : 'siswa',
                'angkatan' => 2026,
                'foto_siswa' => 'siswa.jpg',
                'pembuat' => 'Fixture',
            ], 'id_siswa');
        }

        $pengurusId = (int) DB::table('pengurus_kelas')->insertGetId([
            'id_siswa' => $studentIds['pengurus'],
            'jabatan' => 'Pengurus Kelas',
            'pembuat' => 'Fixture',
        ], 'id_pengurus');

        $attendanceId = 1;
        foreach ($studentIds as $studentId) {
            foreach (['hadir', 'hadir', 'izin', 'alpha'] as $day => $status) {
                DB::table('presensi_siswa')->insert([
                    'id_presensi' => $attendanceId,
                    'id_siswa' => $studentId,
                    'foto_bukti' => 'bukti.png',
                    'jam_masuk' => '07:00:00',
                    'tanggal' => sprintf('2026-01-%02d', $day + 1),
                    'status_kehadiran' => $status,
                    'keterangan' => 'Fixture',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'pembuat' => 'Fixture',
                ]);

                DB::table('validasi')->insert([
                    'id_pengurus' => $pengurusId,
                    'id_presensi' => $attendanceId,
                    'status_validasi' => $status,
                    'waktu_validasi' => 'istirahat_pertama',
                ]);

                $attendanceId++;
            }
        }

        DB::table('logs')->insert([
            'tabel' => 'presensi_siswa',
            'aktor' => 'Fixture',
            'tanggal' => '2026-01-01',
            'jam' => '07:00:00',
            'aksi' => 'Tambah',
            'record' => 'Fixture',
            'status' => 'aktif',
        ]);

        return $accounts;
    }
}
