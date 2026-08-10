<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add database-level invariants for the one-to-one profiles and attendance.
     */
    public function up(): void
    {
        Schema::table('role_akun', function (Blueprint $table): void {
            $table->unique('nama_role', 'role_akun_nama_role_unique');
        });

        Schema::table('akun', function (Blueprint $table): void {
            $table->unique('username', 'akun_username_unique');
        });

        Schema::table('guru', function (Blueprint $table): void {
            $table->unique('id_akun', 'guru_id_akun_unique');
        });

        Schema::table('guru_piket', function (Blueprint $table): void {
            $table->unique('id_guru', 'guru_piket_id_guru_unique');
        });

        Schema::table('guru_bk', function (Blueprint $table): void {
            $table->unique('id_guru', 'guru_bk_id_guru_unique');
        });

        Schema::table('tata_usaha', function (Blueprint $table): void {
            $table->unique('id_akun', 'tata_usaha_id_akun_unique');
        });

        Schema::table('siswa', function (Blueprint $table): void {
            $table->unique('id_akun', 'siswa_id_akun_unique');
            $table->unique('nis', 'siswa_nis_unique');
        });

        Schema::table('pengurus_kelas', function (Blueprint $table): void {
            $table->unique('id_siswa', 'pengurus_kelas_id_siswa_unique');
        });

        Schema::table('presensi_siswa', function (Blueprint $table): void {
            $table->unique(['id_siswa', 'tanggal'], 'presensi_siswa_siswa_tanggal_unique');
        });

        Schema::table('validasi', function (Blueprint $table): void {
            $table->unique(['id_presensi', 'waktu_validasi'], 'validasi_presensi_waktu_unique');
        });

        Schema::table('surat_keterangan', function (Blueprint $table): void {
            $table->unique('id_presensi', 'surat_keterangan_id_presensi_unique');
        });
    }

    /**
     * Remove the invariants added by this migration.
     */
    public function down(): void
    {
        Schema::table('surat_keterangan', function (Blueprint $table): void {
            $table->dropUnique('surat_keterangan_id_presensi_unique');
        });

        Schema::table('validasi', function (Blueprint $table): void {
            $table->dropUnique('validasi_presensi_waktu_unique');
        });

        Schema::table('presensi_siswa', function (Blueprint $table): void {
            $table->dropUnique('presensi_siswa_siswa_tanggal_unique');
        });

        Schema::table('pengurus_kelas', function (Blueprint $table): void {
            $table->dropUnique('pengurus_kelas_id_siswa_unique');
        });

        Schema::table('siswa', function (Blueprint $table): void {
            $table->dropUnique('siswa_id_akun_unique');
            $table->dropUnique('siswa_nis_unique');
        });

        Schema::table('tata_usaha', function (Blueprint $table): void {
            $table->dropUnique('tata_usaha_id_akun_unique');
        });

        Schema::table('guru_bk', function (Blueprint $table): void {
            $table->dropUnique('guru_bk_id_guru_unique');
        });

        Schema::table('guru_piket', function (Blueprint $table): void {
            $table->dropUnique('guru_piket_id_guru_unique');
        });

        Schema::table('guru', function (Blueprint $table): void {
            $table->dropUnique('guru_id_akun_unique');
        });

        Schema::table('akun', function (Blueprint $table): void {
            $table->dropUnique('akun_username_unique');
        });

        Schema::table('role_akun', function (Blueprint $table): void {
            $table->dropUnique('role_akun_nama_role_unique');
        });
    }
};
