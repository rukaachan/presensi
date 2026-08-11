<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$fail = static function (string $message): never {
    fwrite(STDERR, $message.PHP_EOL);
    exit(1);
};

$oldTables = [
    'role_akun', 'akun', 'tata_usaha', 'guru', 'guru_piket', 'guru_bk', 'jurusan',
    'kelas', 'siswa', 'pengurus_kelas', 'presensi_siswa', 'validasi', 'surat_keterangan', 'logs',
];

foreach ($oldTables as $table) {
    if (Schema::hasTable($table)) {
        $fail("Legacy table remains: {$table}");
    }
}

$oldViews = ['view_presensi', 'view_siswa'];
$driver = DB::connection()->getDriverName();
foreach ($oldViews as $view) {
    $exists = $driver === 'sqlite'
        ? DB::table('sqlite_master')->where('type', 'view')->where('name', $view)->exists()
        : DB::table('information_schema.views')
            ->where('table_schema', DB::connection()->getDatabaseName())
            ->where('table_name', $view)
            ->exists();
    if ($exists) {
        $fail("Legacy view remains: {$view}");
    }
}

$expectedCounts = [
    'attendance_records' => 3,
    'attendance_events' => 3,
    'leave_requests' => 1,
    'audit_events' => 2,
];

foreach ($expectedCounts as $table => $expected) {
    $actual = DB::table($table)->count();
    if ($actual !== $expected) {
        $fail("{$table}: expected {$expected} rows, got {$actual}");
    }
}

$record = DB::table('attendance_records')->where('source_record_id', 10)->first();
if ($record === null || $record->state !== 'confirmed' || $record->attendance_session_id === null) {
    $fail('Legacy attendance record 10 was not normalized to a canonical confirmed daily check-in.');
}

$recordStates = DB::table('attendance_records')->orderBy('id')->pluck('state')->all();
if ($recordStates !== ['confirmed', 'needs_review', 'absent']) {
    $fail('Attendance state parity failed: '.json_encode($recordStates));
}

$eventStates = DB::table('attendance_events')->orderBy('id')->pluck('state')->all();
if ($eventStates !== ['confirmed', 'needs_review', 'confirmed']) {
    $fail('Attendance event state parity failed: '.json_encode($eventStates));
}

$checkedOut = DB::table('attendance_events')->where('source_event_id', 22)->first();
if ($checkedOut === null || $checkedOut->proposed_status !== 'checked_out') {
    $fail('Legacy checkout event was not normalized to proposed_status=checked_out.');
}

$leave = DB::table('leave_requests')->where('source_letter_id', 11)->first();
if ($leave === null || $leave->reason !== 'Alasan tidak dicatat pada data lama.') {
    $fail('Null legacy leave reason was not retained with the documented fallback.');
}

$auditActions = DB::table('audit_events')->orderBy('id')->pluck('action')->all();
if ($auditActions !== ['created', 'deleted']) {
    $fail('Audit action parity failed: '.json_encode($auditActions));
}

$evidencePath = 'attendance/evidence/migration-10.png';
$evidenceDisk = (string) config('attendance.evidence_disk', 'local');
if (! Storage::disk($evidenceDisk)->exists($evidencePath)) {
    $fail("Migrated evidence is missing from the configured private disk ({$evidenceDisk}): {$evidencePath}");
}

if (is_file(public_path('presensi_bukti/migration.png'))) {
    $fail('Public legacy evidence was not removed.');
}

echo 'legacy upgrade verification: ok'.PHP_EOL;
