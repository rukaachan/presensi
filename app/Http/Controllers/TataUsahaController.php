<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TataUsahaController extends Controller
{
    public function index()
    {
        $ttlSeconds = 120;
        $operationalDate = now('Asia/Jakarta');
        $date = $operationalDate->toDateString();

        $dailyStats = Cache::remember("dashboard:tata-usaha:daily:{$date}:v1", $ttlSeconds, function () use ($date) {
            return DB::selectOne('
                SELECT
                    (SELECT COUNT(*) FROM siswa WHERE status_siswa = \'aktif\') AS totalActiveStudents,
                    (SELECT COUNT(*) FROM kelas WHERE status_kelas = \'aktif\') AS totalActiveClasses,
                    (SELECT COUNT(*)
                        FROM validasi
                        INNER JOIN presensi_siswa AS validation_attendance
                            ON validation_attendance.id_presensi = validasi.id_presensi
                        WHERE validation_attendance.tanggal = ?
                            AND validasi.status_validasi = \'tidak_ada\') AS pendingValidation,
                    COUNT(DISTINCT presensi_siswa.id_siswa) AS totalRecorded,
                    COALESCE(SUM(CASE WHEN status_kehadiran = \'hadir\' THEN 1 ELSE 0 END), 0) AS totalPresent,
                    COALESCE(SUM(CASE WHEN status_kehadiran = \'izin\' THEN 1 ELSE 0 END), 0) AS totalExcused,
                    COALESCE(SUM(CASE WHEN status_kehadiran = \'alpha\' THEN 1 ELSE 0 END), 0) AS totalAbsent
                FROM presensi_siswa
                WHERE tanggal = ?
            ', [$date, $date]);
        });

        $classReadiness = Cache::remember("dashboard:tata-usaha:classes:{$date}:v1", $ttlSeconds, function () use ($date) {
            return collect(DB::select('
                SELECT
                    kelas.id_kelas,
                    kelas.tingkatan,
                    jurusan.nama_jurusan,
                    kelas.nama_kelas,
                    COUNT(DISTINCT siswa.id_siswa) AS totalStudents,
                    COUNT(DISTINCT presensi_siswa.id_siswa) AS totalRecorded
                FROM kelas
                INNER JOIN jurusan ON jurusan.id_jurusan = kelas.id_jurusan
                LEFT JOIN siswa
                    ON siswa.id_kelas = kelas.id_kelas
                    AND siswa.status_siswa = \'aktif\'
                LEFT JOIN presensi_siswa
                    ON presensi_siswa.id_siswa = siswa.id_siswa
                    AND presensi_siswa.tanggal = ?
                WHERE kelas.status_kelas = \'aktif\'
                GROUP BY
                    kelas.id_kelas,
                    kelas.tingkatan,
                    jurusan.nama_jurusan,
                    kelas.nama_kelas
                ORDER BY kelas.tingkatan DESC, jurusan.nama_jurusan, kelas.nama_kelas
            ', [$date]))->map(function ($class) {
                $totalStudents = (int) $class->totalStudents;
                $totalRecorded = (int) $class->totalRecorded;
                $class->completionRate = $totalStudents > 0
                    ? (int) round(($totalRecorded / $totalStudents) * 100)
                    : 0;
                $class->isComplete = $totalStudents > 0 && $totalRecorded >= $totalStudents;

                return $class;
            });
        });

        $recentLogs = Cache::remember('dashboard:tata-usaha:recent-logs:v1', $ttlSeconds, function () {
            return DB::table('logs')
                ->where('status', 'aktif')
                ->orderByDesc('tanggal')
                ->orderByDesc('jam')
                ->limit(5)
                ->get(['aktor', 'aksi', 'record', 'tanggal', 'jam']);
        });

        $totalActiveStudents = (int) $dailyStats->totalActiveStudents;
        $totalRecorded = (int) $dailyStats->totalRecorded;
        $dailySummary = [
            'totalActiveStudents' => $totalActiveStudents,
            'totalActiveClasses' => (int) $dailyStats->totalActiveClasses,
            'totalRecorded' => $totalRecorded,
            'totalMissing' => max(0, $totalActiveStudents - $totalRecorded),
            'totalPresent' => (int) $dailyStats->totalPresent,
            'totalExcused' => (int) $dailyStats->totalExcused,
            'totalAbsent' => (int) $dailyStats->totalAbsent,
            'pendingValidation' => (int) $dailyStats->pendingValidation,
            'needsReview' => (int) $dailyStats->totalAbsent + (int) $dailyStats->pendingValidation,
            'completionRate' => $totalActiveStudents > 0
                ? (int) round(($totalRecorded / $totalActiveStudents) * 100)
                : 0,
            'classesComplete' => $classReadiness->where('isComplete', true)->count(),
        ];

        return view('tata-usaha.index', compact(
            'classReadiness',
            'dailySummary',
            'operationalDate',
            'recentLogs'
        ));
    }
}
