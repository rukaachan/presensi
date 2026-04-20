<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\PresensiSiswa;
use App\Services\PresensiFilterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuruBkController extends Controller
{
    public function __construct(private PresensiFilterService $presensiFilterService)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $statusTotals = DB::selectOne("\n            SELECT\n                COALESCE(SUM(CASE WHEN status_kehadiran = 'Hadir' THEN 1 ELSE 0 END), 0) AS totalHadir,\n                COALESCE(SUM(CASE WHEN status_kehadiran = 'Izin' THEN 1 ELSE 0 END), 0) AS totalIzin,\n                COALESCE(SUM(CASE WHEN status_kehadiran = 'Alpha' THEN 1 ELSE 0 END), 0) AS totalAlpha\n            FROM presensi_siswa\n        ");

        $totalHadir = $statusTotals->totalHadir;
        $totalIzin = $statusTotals->totalIzin;
        $totalAlpha = $statusTotals->totalAlpha;

        return view('guru-bk.index', compact('totalHadir', 'totalIzin', 'totalAlpha'));
    }

    public function detailProfil(Request $request, Guru $guru)
    {
        $id_guru = $guru->where('id_akun', $request->id)->first()->id_guru;
        $data = [
            'guru' => $guru
                ->join('akun', 'guru.id_akun', '=', 'akun.id_akun')
                ->where('id_guru', $id_guru)->first(),
        ];

        return view('guru-bk.detail-profil', $data);
    }

    public function showPresensi(Request $request, Kelas $kelas, PresensiSiswa $presensi)
    {
        $query = $this->presensiFilterService->buildBaseQuery($presensi);
        $filter = $this->presensiFilterService->filter($request, $query);
        $data = [
            'presensi' => $filter,
            'kelas' => $kelas->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')->orderBy('tingkatan')->orderBy('nama_kelas')->get(),
        ];

        return view('guru-bk.presensi', $data);
    }

    public function detailPresensi(Request $request, PresensiSiswa $presensi)
    {
        $data = [
            'presensi' => $presensi
                ->join('siswa', 'siswa.id_siswa', '=', 'presensi_siswa.id_siswa')
                ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
                ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
                ->where('id_presensi', $request->id)->first(),
        ];

        return view('guru-bk.detail-presensi', $data);
    }

    public function exportPresensi(Request $request, PresensiSiswa $presensi)
    {
        $query = $this->presensiFilterService->buildBaseQuery($presensi);
        $filter = $this->presensiFilterService->filter($request, $query, false);
        $pdf = Pdf::loadView('presensi-pdf', ['presensi' => $filter]);

        return $pdf->download('presensi.pdf');
    }
}
