<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store the configurable attendance session catalog.
     */
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label', 100);
            $table->string('kind', 30);
            $table->string('legacy_code', 50)->nullable()->unique();
            $table->boolean('required')->default(false);
            $table->boolean('active')->default(true);
            $table->time('window_start')->nullable();
            $table->time('window_end')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['active', 'sort_order']);
        });
    }

    /**
     * Remove the attendance session catalog.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
