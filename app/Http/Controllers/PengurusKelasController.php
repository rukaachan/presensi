<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePresensiRequest;
use App\Models\AttendanceRecord;
use App\Models\PengurusKelas;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
use App\Models\Validasi;
use App\Services\AttendanceEvidenceStorage;
use App\Services\AttendanceService;
use App\Services\AttendanceSessionCatalog;
use App\Services\LegacyAttendanceWriteAdapter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class PengurusKelasController extends Controller
{
    public function __construct(
        private AttendanceSessionCatalog $attendanceSessionCatalog,
        private AttendanceService $attendanceService,
        private AttendanceEvidenceStorage $evidenceStorage,
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

        $timezone = (string) config('attendance.timezone', 'Asia/Jakarta');
        $date = now($timezone)->toDateString();
        $legacyExists = PresensiSiswa::where('id_siswa', $target->id_siswa)
            ->whereDate('tanggal', $date)
            ->exists();
        $session = $this->attendanceSessionCatalog->required();
        $targetExists = $session !== null && AttendanceRecord::query()
            ->where('student_id', $target->getKey())
            ->where('attendance_session_id', $session->getKey())
            ->whereDate('attendance_date', $date)
            ->exists();

        return response()->json(['exists' => $legacyExists || $targetExists]);
    }

    public function showKelas(Request $request, Siswa $siswa, Validasi $validasi)
    {
        $siswa = $siswa
            ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->join('akun', 'siswa.id_akun', '=', 'akun.id_akun')
            ->where('akun.id_akun', Auth::user()->id_akun)
            ->first();

        $validationSessions = $this->attendanceSessionCatalog->validationSessions();
        $validationCodes = $this->attendanceSessionCatalog->validationCodes();
        $selectedValidationCode = $request->input('waktu_validasi') ?: ($validationCodes[0] ?? null);

        if ($siswa === null) {
            return view('pengurus-kelas.kelas', [
                'data' => collect([]),
                'validationSessions' => $validationSessions,
                'selectedValidationCode' => $selectedValidationCode,
            ]);
        }

        $filter = $this->getKelasData($siswa, $validasi, $validationCodes);

        return view('pengurus-kelas.kelas', [
            'data' => $filter,
            'validationSessions' => $validationSessions,
            'selectedValidationCode' => $selectedValidationCode,
        ]);
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

        $validationCodes = $this->attendanceSessionCatalog->validationCodes();
        $filter = $this->getKelasData($siswa, $validasi, $validationCodes);

        // Remove duplicates based on id_presensi
        $filter = collect($filter)->unique('id_presensi')->values()->all();

        $pdf = Pdf::loadView('pengurus-kelas.kelas-pdf', ['kelas' => $filter]);

        return $pdf->download('kelas.pdf');
    }

    /**
     * @param  list<string>  $validationCodes
     */
    private function getKelasData($siswa, $validasi, array $validationCodes)
    {
        return $validasi
            ->join('presensi_siswa', 'validasi.id_presensi', '=', 'presensi_siswa.id_presensi')
            ->join('siswa', 'presensi_siswa.id_siswa', '=', 'siswa.id_siswa')
            ->join('akun', 'siswa.id_akun', '=', 'akun.id_akun')
            ->where('siswa.id_kelas', $siswa->id_kelas)
            ->whereIn('validasi.waktu_validasi', $validationCodes)
            ->get();
    }

    public function updateValidasi(Request $request)
    {
        $allowedValidationCodes = $this->attendanceSessionCatalog->validationCodes();
        $request->validate([
            'waktu_validasi' => ['required', Rule::in($allowedValidationCodes)],
            'status_validasi' => ['required', 'array'],
            'status_validasi.*' => ['required', 'array'],
            'status_validasi.*.*' => ['required', Rule::in(['hadir', 'izin', 'alpha', 'pulang'])],
        ]);

        $actor = Auth::user();
        $validationCode = (string) $request->input('waktu_validasi');
        $session = $this->attendanceSessionCatalog->active()
            ->firstWhere('legacy_code', $validationCode);

        foreach ($request->input('status_validasi') as $index => $statuses) {
            $status = is_array($statuses) ? (string) reset($statuses) : (string) $statuses;
            $legacy = PresensiSiswa::query()
                ->where('id_presensi', $request->input("id_presensi.$index"))
                ->first();
            $student = $legacy?->siswa()->first();

            if ($session === null || $legacy === null || ! $student instanceof Siswa || $status === '') {
                continue;
            }

            $this->attendanceService->suggestOptionalEvent(
                $actor,
                $student,
                $session->code,
                $status,
                [
                    'event_date' => $legacy->getRawOriginal('tanggal'),
                    'observed_at' => now((string) config('attendance.timezone', 'Asia/Jakarta')),
                    'idempotency_key' => 'validation:'.$legacy->getKey().':'.$session->getKey(),
                    'notes' => 'Usulan status dari pengurus kelas: '.$status,
                    'source' => 'class_officer',
                ],
            );

            $existingValidasi = Validasi::query()
                ->where('id_pengurus', $request->input("id_pengurus.$index"))
                ->where('id_presensi', $legacy->getKey())
                ->where('waktu_validasi', $validationCode)
                ->first();

            if ($existingValidasi !== null) {
                $existingValidasi->update(['status_validasi' => $status]);
            }
        }

        return back()->with('success', 'Usulan validasi sudah dikirim untuk ditinjau.');
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

        $timezone = (string) config('attendance.timezone', 'Asia/Jakarta');
        $date = now($timezone)->toDateString();
        if (PresensiSiswa::where('id_siswa', $targetSiswa->id_siswa)
            ->whereDate('tanggal', $date)
            ->exists()) {
            return back()->withInput()->with('error', 'Presensi untuk hari ini sudah tercatat.');
        }

        $image = $request->input('image');
        if (! is_string($image)) {
            return back()->withInput()->with('error', 'Format gambar tidak valid.');
        }

        $actor = Auth::user();
        $evidence = null;
        try {
            $evidence = $this->evidenceStorage->storeDataUri($image);
            DB::transaction(function () use ($actor, $targetSiswa, $evidence, $timezone, $date): void {
                $record = $this->attendanceService->recordDailyCheckIn(
                    $actor,
                    $targetSiswa,
                    [
                        'attendance_date' => $date,
                        'captured_at' => now($timezone),
                        'evidence_disk' => $evidence['disk'],
                        'evidence_path' => $evidence['path'],
                        'evidence_hash' => $evidence['hash'],
                        'evidence_mime' => $evidence['mime'],
                        'evidence_bytes' => $evidence['bytes'],
                        'notes' => 'Presensi melalui kamera oleh pengurus kelas',
                        'source' => 'class_officer',
                    ],
                );

                $legacy = $this->legacyAttendance->createDailyCapture(
                    $targetSiswa,
                    now($timezone),
                    'hadir',
                    'Presensi melalui kamera oleh pengurus kelas',
                    'Pengurus Kelas',
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
