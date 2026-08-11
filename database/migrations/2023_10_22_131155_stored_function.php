<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @deprecated MySQL functions are replaced by portable application queries.
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

        foreach ([
            'CountTeachers',
            'CountBkTeachers',
            'CountPiketTeachers',
            'CountClasses',
            'CountClassMembers',
            'CountWaliKelas',
            'CountStudents',
            'CountTotalStudents',
            'CountStatus',
        ] as $function) {
            DB::unprepared("DROP FUNCTION IF EXISTS {$function}");
        }
    }
};
