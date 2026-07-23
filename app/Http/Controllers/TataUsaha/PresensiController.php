<?php

namespace App\Http\Controllers\TataUsaha;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\PresensiSiswa;
use App\Services\PresensiFilterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PresensiController extends Controller
{
    public function __construct(private PresensiFilterService $presensiFilterService) {}

    public function showPresensi(Request $request, Kelas $kelas, PresensiSiswa $presensi)
    {
        $query = $this->presensiFilterService->buildBaseQuery($presensi);
        $filter = $this->presensiFilterService->filter($request, $query);
        $data = [
            'presensi' => $filter,
            'kelas' => $kelas->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')->orderBy('tingkatan')->orderBy('nama_kelas')->get(),
        ];

        return view('tata-usaha.presensi', $data);
    }

    public function exportPresensi(Request $request, PresensiSiswa $presensi)
    {
        $query = $this->presensiFilterService->buildBaseQuery($presensi);
        $filter = $this->presensiFilterService->filter($request, $query, false);
        $pdf = Pdf::loadView('presensi-pdf', ['presensi' => $filter]);

        return $pdf->download('presensi.pdf');
    }
}
