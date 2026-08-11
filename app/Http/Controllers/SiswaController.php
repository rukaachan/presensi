<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePresensiRequest;
use App\Models\AttendanceRecord;
use App\Models\PengurusKelas;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
use App\Services\AttendanceEvidenceStorage;
use App\Services\AttendanceService;
use App\Services\AttendanceSessionCatalog;
use App\Services\LegacyAttendanceWriteAdapter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class SiswaController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
        private AttendanceEvidenceStorage $evidenceStorage,
        private AttendanceSessionCatalog $sessionCatalog,
        private LegacyAttendanceWriteAdapter $legacyAttendance,
    ) {}

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

        return view('siswa.index', [
            'totalHadir' => (int) $totals->totalHadir,
            'totalIzin' => (int) $totals->totalIzin,
            'totalAlpha' => (int) $totals->totalAlpha,
        ]);
    }

    public function detailProfil(Request $request, Siswa $siswa, PengurusKelas $pengurus)
    {
        $siswaRecord = $siswa->where('id_akun', $request->id)->first();
        if ($siswaRecord === null) {
            return view('layout.profile-unavailable', [
                'message' => 'Akun siswa ini belum terhubung dengan data siswa.',
                'backUrl' => route('siswa.dashboard'),
            ]);
        }

        $id_siswa = $siswaRecord->id_siswa;
        $data = [
            'siswa' => $siswa
                ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
                ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
                ->where('id_siswa', $id_siswa)->first(),
            'pengurus' => $pengurus->where('id_siswa', $id_siswa)->first(),
        ];

        return view('siswa.detail-profil', $data);
    }

    public function checkSnapshot(Request $request)
    {
        $siswa = Siswa::where('id_siswa', $request->input('id_siswa'))
            ->where('id_akun', Auth::user()->id_akun)
            ->first();

        abort_unless($siswa !== null, 403);

        $timezone = (string) config('attendance.timezone', 'Asia/Jakarta');
        $date = now($timezone)->toDateString();
        $legacyExists = PresensiSiswa::where('id_siswa', $siswa->id_siswa)
            ->whereDate('tanggal', $date)
            ->exists();
        $session = $this->sessionCatalog->required();
        $targetExists = $session !== null && AttendanceRecord::query()
            ->where('student_id', $siswa->getKey())
            ->where('attendance_session_id', $session->getKey())
            ->whereDate('attendance_date', $date)
            ->exists();

        return response()->json(['exists' => $legacyExists || $targetExists]);
    }

    public function openCam(Siswa $siswa)
    {
        $user = Auth::user()->id_akun;
        $siswaData = $siswa
            ->join('akun', 'siswa.id_akun', '=', 'akun.id_akun')
            ->where('siswa.id_akun', $user)
            ->first();

        return view('siswa.presensi', ['siswa' => $siswaData]);
    }

    public function store(StorePresensiRequest $request)
    {
        $siswa = Siswa::where('id_akun', Auth::user()->id_akun)->first();
        if (! $siswa) {
            return back()->withInput()->with('error', 'Data siswa tidak ditemukan.');
        }

        $timezone = (string) config('attendance.timezone', 'Asia/Jakarta');
        $date = now($timezone)->toDateString();
        if (PresensiSiswa::where('id_siswa', $siswa->id_siswa)
            ->whereDate('tanggal', $date)
            ->exists()) {
            return back()->withInput()->with('error', 'Presensi untuk hari ini sudah tercatat.');
        }

        $image = $request->input('image');
        if (! is_string($image)) {
            return back()->withInput()->with('error', 'Format gambar tidak valid.');
        }

        $evidence = null;
        try {
            $evidence = $this->evidenceStorage->storeDataUri($image);
            DB::transaction(function () use ($siswa, $evidence, $timezone, $date): void {
                $record = $this->attendanceService->recordDailyCheckIn(
                    Auth::user(),
                    $siswa,
                    [
                        'attendance_date' => $date,
                        'captured_at' => now($timezone),
                        'evidence_disk' => $evidence['disk'],
                        'evidence_path' => $evidence['path'],
                        'evidence_hash' => $evidence['hash'],
                        'evidence_mime' => $evidence['mime'],
                        'evidence_bytes' => $evidence['bytes'],
                        'notes' => 'Presensi melalui kamera',
                        'source' => 'student',
                    ],
                );

                $legacy = $this->legacyAttendance->createDailyCapture(
                    $siswa,
                    now($timezone),
                    'hadir',
                    'Presensi melalui kamera',
                    'Siswa',
                );
                $this->legacyAttendance->link($record, $legacy);
            });
        } catch (Throwable $exception) {
            if ($evidence !== null) {
                $this->evidenceStorage->delete($evidence['path'], $evidence['disk']);
            }

            report($exception);

            return back()->withInput()->with('error', 'Presensi gagal disimpan. Silakan coba lagi.');
        }

        session(['snapshot_taken' => true]);

        return back()->with('success', 'Presensi berhasil dikirim untuk ditinjau.');
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

        return view('siswa.histori', compact('filter', 'bulanList', 'mingguList', 'selectedMonth', 'selectedWeek'));
    }

    public function exportPresensi(Request $request, PresensiSiswa $presensi)
    {
        $filter = $this->getFilteredData($request, $presensi);

        $pdf = Pdf::loadView('siswa.presensi-pdf', ['presensi' => $filter]);

        return $pdf->download('presensi.pdf');
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
}
