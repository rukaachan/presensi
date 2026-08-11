<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create absence and leave requests.
     */
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table): void {
            $table->id();
            $table->integer('student_id');
            $table->foreignId('attendance_record_id')
                ->nullable()
                ->constrained('attendance_records')
                ->nullOnDelete();
            $table->string('state', 24)->default('submitted');
            $table->text('reason');
            $table->string('attachment_disk', 50)->nullable();
            $table->string('attachment_path')->nullable();
            $table->integer('submitted_by')->nullable();
            $table->integer('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->integer('legacy_surat_presensi_id')->nullable()->unique();
            $table->timestamps();

            $table->foreign('student_id')
                ->references('id_siswa')
                ->on('siswa')
                ->restrictOnDelete();
            $table->foreign('submitted_by')
                ->references('id_akun')
                ->on('akun')
                ->nullOnDelete();
            $table->foreign('reviewed_by')
                ->references('id_akun')
                ->on('akun')
                ->nullOnDelete();

            $table->index(['student_id', 'state']);
            $table->index(['state', 'reviewed_at']);
        });
    }

    /**
     * Drop absence and leave requests.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
