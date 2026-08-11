<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove hidden business rules after parity has moved into services.
     */
    public function up(): void
    {
        foreach ([
            'add_siswa',
            'update_siswa',
            'delete_siswa',
            'add_pengurus',
            'update_presensi_siswa',
            'update_pengurus',
            'delete_pengurus',
            'add_guru',
            'update_guru',
            'delete_guru',
            'add_jurusan',
            'update_jurusan',
            'delete_jurusan',
            'add_kelas',
            'update_kelas',
            'delete_kelas',
            'insert_validasi',
        ] as $trigger) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach (['CreateTeacherBK', 'CreateDutyTeacher', 'CreateWaliClassroom'] as $procedure) {
            DB::unprepared("DROP PROCEDURE IF EXISTS {$procedure}");
        }

        foreach ([
            'CountTeachers',
            'CountBkTeachers',
            'CountPiketTeachers',
            'CountClasses',
            'CountClassMembers',
            'CountWaliClassroom',
            'CountStudents',
            'CountTotalStudents',
            'CountStatus',
        ] as $function) {
            DB::unprepared("DROP FUNCTION IF EXISTS {$function}");
        }
    }

    /**
     * The old routines are intentionally not recreated; application services
     * are the portable source of truth after this migration.
     */
    public function down(): void
    {
        // Irreversible deprecation. Restore from a pre-deprecation backup if required.
    }
};
