<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create optional attendance event records.
     */
    public function up(): void
    {
        Schema::create('attendance_events', function (Blueprint $table): void {
            $table->id();
            $table->integer('student_id');
            $table->foreignId('attendance_session_id')
                ->constrained('attendance_sessions')
                ->restrictOnDelete();
            $table->date('event_date');
            $table->string('state', 32)->default('submitted');
            $table->string('proposed_status', 32)->nullable();
            $table->timestamp('observed_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('source', 32)->default('legacy');
            $table->integer('observed_by')->nullable();
            $table->integer('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->integer('legacy_validasi_id')->nullable()->unique();
            $table->integer('legacy_presensi_id')->nullable();
            $table->timestamps();

            $table->foreign('student_id')
                ->references('id_siswa')
                ->on('siswa')
                ->restrictOnDelete();
            $table->foreign('observed_by')
                ->references('id_akun')
                ->on('akun')
                ->nullOnDelete();
            $table->foreign('reviewed_by')
                ->references('id_akun')
                ->on('akun')
                ->nullOnDelete();

            $table->unique(
                ['student_id', 'attendance_session_id', 'event_date'],
                'attendance_events_student_session_date_unique',
            );
            $table->index(['state', 'event_date']);
            $table->index(['legacy_presensi_id', 'event_date']);
        });
    }

    /**
     * Drop optional attendance event records.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_events');
    }
};
