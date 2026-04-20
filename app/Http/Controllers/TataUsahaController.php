<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TataUsahaController extends Controller
{
    public function index()
    {
        $ttlSeconds = 120;

        $teacherStats = Cache::remember('dashboard:tata-usaha:teachers:v1', $ttlSeconds, function () {
            return DB::selectOne('
                SELECT
                    (SELECT COUNT(*) FROM guru) AS totalGuru,
                    (SELECT COUNT(*) FROM guru_bk) AS totalGuruBk,
                    (SELECT COUNT(*) FROM guru_piket) AS totalGuruPiket,
                    COALESCE(SUM(CASE WHEN id_wali_kelas IS NOT NULL THEN 1 ELSE 0 END), 0) AS totalWaliKelas
                FROM kelas
            ');
        });

        $classStats = Cache::remember('dashboard:tata-usaha:classes:v1', $ttlSeconds, function () {
            return DB::selectOne('SELECT COUNT(*) AS totalKelas FROM kelas');
        });

        $studentAndMemberStats = Cache::remember('dashboard:tata-usaha:students-members:v1', $ttlSeconds, function () {
            return DB::selectOne('
                SELECT
                    SUM(CASE WHEN metric = "pengurus_kelas" THEN total ELSE 0 END) AS totalPengurusKelas,
                    SUM(CASE WHEN metric = "siswa" THEN total ELSE 0 END) AS totalSiswa
                FROM (
                    SELECT "pengurus_kelas" AS metric, COUNT(*) AS total FROM pengurus_kelas
                    UNION ALL
                    SELECT "siswa" AS metric, COUNT(*) AS total FROM siswa
                ) AS aggregated_totals
            ');
        });

        $totalGuru = $teacherStats->totalGuru;
        $totalGuruBk = $teacherStats->totalGuruBk;
        $totalGuruPiket = $teacherStats->totalGuruPiket;
        $totalWaliKelas = $teacherStats->totalWaliKelas;
        $totalKelas = $classStats->totalKelas;
        $totalPengurusKelas = $studentAndMemberStats->totalPengurusKelas;
        $totalSiswa = $studentAndMemberStats->totalSiswa;

        return view('tata-usaha.index', compact('totalGuru', 'totalGuruBk', 'totalGuruPiket', 'totalKelas', 'totalPengurusKelas', 'totalSiswa', 'totalWaliKelas'));
    }
}
