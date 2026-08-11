<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @deprecated Procedures are replaced by transactional application services.
     */
    public function up(): void
    {
        // Existing installations are cleaned by the deprecation migration.
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach (['CreateTeacherBK', 'CreateDutyTeacher', 'CreateWaliClassroom'] as $procedure) {
            DB::unprepared("DROP PROCEDURE IF EXISTS {$procedure}");
        }
    }
};
