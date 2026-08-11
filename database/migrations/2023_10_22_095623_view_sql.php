<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Legacy compatibility views are intentionally not created.
     * The English-contract migration creates only canonical views.
     */
    public function up(): void
    {
        // No-op for fresh installs; retained for migration history.
    }

    public function down(): void
    {
        // Legacy views are not recreated on rollback.
    }
};
