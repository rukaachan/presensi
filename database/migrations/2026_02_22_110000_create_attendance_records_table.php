<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the target daily attendance record table.
     */
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->integer('student_id');
            $table->foreignId('attendance_session_id')
                ->constrained('attendance_sessions')
                ->restrictOnDelete();
            $table->date('attendance_date');
            $table->string('state', 32)->default('submitted');
            $table->boolean('late')->default(false);
            $table->timestamp('captured_at')->nullable();
            $table->string('evidence_disk', 50)->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('evidence_hash', 64)->nullable();
            $table->string('evidence_mime', 50)->nullable();
            $table->unsignedInteger('evidence_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->string('source', 32)->default('legacy');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->integer('legacy_presensi_id')->nullable()->unique();
            $table->timestamps();

            $table->foreign('student_id')
                ->references('id_siswa')
                ->on('siswa')
                ->restrictOnDelete();
            $table->foreign('created_by')
                ->references('id_akun')
                ->on('akun')
                ->nullOnDelete();
            $table->foreign('updated_by')
                ->references('id_akun')
                ->on('akun')
                ->nullOnDelete();

            $table->unique(
                ['student_id', 'attendance_session_id', 'attendance_date'],
                'attendance_records_student_session_date_unique',
            );
            $table->index(['student_id', 'attendance_date']);
            $table->index(['state', 'attendance_date']);
        });
    }

    /**
     * Drop the target daily attendance record table.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
