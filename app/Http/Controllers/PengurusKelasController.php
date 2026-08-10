<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePresensiRequest;
use App\Models\PengurusKelas;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
use App\Models\Validasi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PengurusKelasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user()->id_akun;

        $totals = PresensiSiswa::query()
            ->selectRaw('COALESCE(SUM(CASE WHEN presensi_siswa.status_kehadiran = ? THEN 1 ELSE 0 END), 0) as totalHadir', ['hadir'])
            ->selectRaw('COALESCE(SUM(CASE WHEN presensi_siswa.status_kehadiran = ? THEN 1 ELSE 0 END), 0) as totalIzin', ['izin'])
            ->selectRaw('COALESCE(SUM(CASE WHEN presensi_siswa.status_kehadiran = ? THEN 1 ELSE 0 END), 0) as totalAlpha', ['alpha'])
            ->joinSiswaKelas()
            ->where('siswa.id_akun', $user)
            ->toBase()
            ->first();

        return view('pengurus-kelas.index', [
            'totalHadir' => (int) $totals->totalHadir,
            'totalIzin' => (int) $totals->totalIzin,
            'totalAlpha' => (int) $totals->totalAlpha,
        ]);
    }

    public function detailProfil(Request $request, PengurusKelas $pengurus)
    {
        $pengurusRecord = $pengurus
            ->join('siswa', 'pengurus_kelas.id_siswa', '=', 'siswa.id_siswa')
            ->where('id_akun', $request->id)
            ->first();

        if ($pengurusRecord === null) {
            return view('layout.profile-unavailable', [
                'message' => 'Akun pengurus kelas ini belum terhubung dengan data siswa.',
                'backUrl' => route('pengurus-kelas.dashboard'),
            ]);
        }

        $id_pengurus = $pengurusRecord->id_pengurus;
        $data = [
            'pengurus' => $pengurus
                ->join('siswa', 'pengurus_kelas.id_siswa', '=', 'siswa.id_siswa')
                ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
                ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
                ->where('id_pengurus', $id_pengurus)->first(),
        ];

        return view('pengurus-kelas.detail-profil', $data);
    }

    public function openCam(Siswa $siswa)
    {
        $user = Auth::user()->id_akun;
        $siswaData = $siswa
            ->join('akun', 'siswa.id_akun', '=', 'akun.id_akun')
            ->where('siswa.id_akun', $user)
            ->first();

        return view('pengurus-kelas.presensi', ['siswa' => $siswaData]);
    }

    public function checkSnapshot(Request $request)
    {
        $pengurus = Siswa::where('id_akun', Auth::user()->id_akun)->first();
        $target = Siswa::where('id_siswa', $request->input('id_siswa'))
            ->where('id_kelas', $pengurus?->id_kelas)
            ->first();

        abort_unless($pengurus !== null && $target !== null, 403);

        $exists = PresensiSiswa::where('id_siswa', $target->id_siswa)
            ->whereDate('tanggal', now('Asia/Jakarta')->toDateString())
            ->exists();

        return response()->json(['exists' => $exists]);
    }

    public function showKelas(Request $request, Siswa $siswa, Validasi $validasi)
    {
        $siswa = $siswa
            ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->join('akun', 'siswa.id_akun', '=', 'akun.id_akun')
            ->where('akun.id_akun', Auth::user()->id_akun)
            ->first();

        if ($siswa === null) {
            return view('pengurus-kelas.kelas', ['data' => collect([])]);
        }

        $waktuValidasi = $request->input('waktu_validasi');

        $filter = $this->getKelasData($siswa, $validasi, $waktuValidasi);

        if ($filter->isNotEmpty()) {
            return view('pengurus-kelas.kelas', ['data' => $filter]);
        }

        return view('pengurus-kelas.kelas', ['data' => collect([])]);
    }

    public function exportKelas(Request $request, PresensiSiswa $presensi, Siswa $siswa, Validasi $validasi)
    {
        $siswa = $siswa
            ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->join('akun', 'siswa.id_akun', '=', 'akun.id_akun')
            ->where('akun.id_akun', Auth::user()->id_akun)
            ->first();

        if ($siswa === null) {
            return back()->with('error', 'Profil pengurus belum terhubung dengan kelas.');
        }

        $waktuValidasi = $request->input('waktu_validasi');

        $filter = $this->getKelasData($siswa, $validasi, $waktuValidasi);

        // Remove duplicates based on id_presensi
        $filter = collect($filter)->unique('id_presensi')->values()->all();

        $pdf = Pdf::loadView('pengurus-kelas.kelas-pdf', ['kelas' => $filter]);

        return $pdf->download('kelas.pdf');
    }

    private function getKelasData($siswa, $validasi, $waktuValidasi)
    {
        return $validasi
            ->join('presensi_siswa', 'validasi.id_presensi', '=', 'presensi_siswa.id_presensi')
            ->join('siswa', 'presensi_siswa.id_siswa', '=', 'siswa.id_siswa')
            ->join('akun', 'siswa.id_akun', '=', 'akun.id_akun')
            ->where('siswa.id_kelas', $siswa->id_kelas)
            ->where(function ($query) use ($waktuValidasi) {
                $query->where('validasi.waktu_validasi', $waktuValidasi)
                    ->orWhere('validasi.waktu_validasi', 'istirahat_pertama')
                    ->orWhere('validasi.waktu_validasi', 'istirahat_kedua')
                    ->orWhere('validasi.waktu_validasi', 'istirahat_ketiga');
            })
            ->get();
    }

    public function updateValidasi(Request $request)
    {
        $request->validate([
            'waktu_validasi' => 'required',
        ]);

        foreach ($request->input('status_validasi') as $index => $statuses) {
            foreach ($statuses as $status) {
                $existingValidasi = Validasi::where('id_pengurus', $request->input("id_pengurus.$index"))
                    ->where('id_presensi', $request->input("id_presensi.$index"))
                    ->where('waktu_validasi', $request->input('waktu_validasi'))
                    ->first();

                if ($existingValidasi) {
                    $existingValidasi->update([
                        'status_validasi' => $status,
                    ]);
                }
            }
        }

        return back()->with('success', 'Data validasi sudah diupdate');
    }

    public function store(StorePresensiRequest $request)
    {
        $pengurusSiswa = Siswa::where('id_akun', Auth::user()->id_akun)->first();
        if (! $pengurusSiswa) {
            return back()->withInput()->with('error', 'Data pengurus tidak ditemukan.');
        }

        $targetSiswa = Siswa::where('id_siswa', $request->input('id_siswa'))->first();
        if (! $targetSiswa || $targetSiswa->id_kelas !== $pengurusSiswa->id_kelas) {
            return back()->withInput()->with('error', 'Tidak memiliki akses untuk siswa ini.');
        }

        if (PresensiSiswa::where('id_siswa', $targetSiswa->id_siswa)
            ->whereDate('tanggal', now('Asia/Jakarta')->toDateString())
            ->exists()) {
            return back()->withInput()->with('error', 'Presensi untuk hari ini sudah tercatat.');
        }

        $image = $request->input('image');
        if (! is_string($image) || ! preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $image)) {
            return back()->withInput()->with('error', 'Format gambar tidak valid.');
        }

        [, $imageData] = explode(';base64,', $image, 2);

        $maxImageSizeBytes = 2 * 1024 * 1024;
        $padding = str_ends_with($imageData, '==') ? 2 : (str_ends_with($imageData, '=') ? 1 : 0);
        $estimatedSize = (int) floor((strlen($imageData) * 3) / 4) - $padding;

        if ($estimatedSize <= 0 || $estimatedSize > $maxImageSizeBytes) {
            return back()->withInput()->with('error', 'Ukuran gambar terlalu besar. Maksimal 2MB.');
        }

        $imageBase64 = base64_decode($imageData, true);
        if ($imageBase64 === false || strlen($imageBase64) > $maxImageSizeBytes) {
            return back()->withInput()->with('error', 'Data gambar tidak valid.');
        }

        $folderPath = 'presensi_bukti';
        $fileName = Str::uuid().'.png';
        $filePath = public_path("$folderPath/$fileName");

        file_put_contents($filePath, $imageBase64);
        PresensiSiswa::create([
            'id_siswa' => $targetSiswa->id_siswa,
            'foto_bukti' => $fileName,
            'jam_masuk' => now('Asia/Jakarta')->format('H:i:s'),
            'tanggal' => now('Asia/Jakarta')->toDateString(),
            'status_kehadiran' => 'hadir',
            'keterangan' => 'Presensi melalui kamera',
            'pembuat' => 'Siswa',

        ]);

        session(['snapshot_taken' => true]);

        return back()->with('success', 'Image uploaded successfully');
    }

    public function showHistori(Request $request, PresensiSiswa $presensi)
    {
        $filter = $this->getFilteredData($request, $presensi);

        $bulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei',
            6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober',
            11 => 'November', 12 => 'Desember',
        ];

        $mingguList = [1, 2, 3, 4];
        $selectedMonth = $request->input('bulan', null);
        $selectedWeek = $request->input('minggu', null);

        return view('pengurus-kelas.histori', compact('filter', 'bulanList', 'mingguList', 'selectedMonth', 'selectedWeek'));
    }

    private function getFilteredData(Request $request, PresensiSiswa $presensi)
    {
        $selectedMonth = $request->input('bulan', null);
        $selectedWeek = $request->input('minggu', null);

        $dayExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%d', presensi_siswa.tanggal) AS INTEGER)"
            : 'DAY(presensi_siswa.tanggal)';

        return $presensi::selectRaw("*,
            CASE
                WHEN {$dayExpression} <= 7 THEN 'Minggu ke-1'
                WHEN {$dayExpression} <= 14 THEN 'Minggu ke-2'
                WHEN {$dayExpression} <= 21 THEN 'Minggu ke-3'
                ELSE 'Minggu ke-4'
            END AS minggu")
            ->joinSiswa()
            ->where('siswa.id_akun', Auth::user()->id_akun)
            ->when($selectedMonth, function ($query, $selectedMonth) {
                $query->whereMonth('presensi_siswa.tanggal', $selectedMonth);
            })
            ->when($selectedWeek, function ($query, $selectedWeek) use ($dayExpression) {
                $query->whereRaw("{$dayExpression} BETWEEN ? AND ?", match ((int) $selectedWeek) {
                    1 => [1, 7],
                    2 => [8, 14],
                    3 => [15, 21],
                    4 => [22, 31],
                    default => [1, 0],
                });
            })
            ->get();
    }

    public function exportPresensi(Request $request, PresensiSiswa $presensi)
    {
        $filter = $this->getFilteredData($request, $presensi);

        $pdf = Pdf::loadView('pengurus-kelas.presensi-pdf', ['presensi' => $filter]);

        return $pdf->download('presensi.pdf');
    }
}
