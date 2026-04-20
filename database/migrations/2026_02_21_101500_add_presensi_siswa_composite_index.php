<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('presensi_siswa', function (Blueprint $table): void {
            $table->index(['id_siswa', 'status_kehadiran'], 'idx_presensi_siswa_id_siswa_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presensi_siswa', function (Blueprint $table): void {
            $table->dropIndex('idx_presensi_siswa_id_siswa_status');
        });
    }
};
