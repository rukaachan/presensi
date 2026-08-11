<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create append-only audit events for the new domain.
     */
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->integer('actor_id')->nullable();
            $table->string('actor_type', 80)->nullable();
            $table->string('legacy_actor', 120)->nullable();
            $table->string('action', 80);
            $table->string('subject_type', 120)->nullable();
            $table->string('subject_id', 80)->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->integer('legacy_log_id')->nullable()->unique();
            $table->timestamps();

            $table->foreign('actor_id')
                ->references('id_akun')
                ->on('akun')
                ->nullOnDelete();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['action', 'occurred_at']);
        });
    }

    /**
     * Drop append-only audit events.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
