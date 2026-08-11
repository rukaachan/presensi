<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @deprecated Trigger-generated audit and validation rows are replaced by
     * application services and explicit constraints.
     */
    public function up(): void
    {
        // Existing installations are cleaned by the deprecation migration.
    }

    public function down(): void
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
    }
};
