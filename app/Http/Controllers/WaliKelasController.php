<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\AttendanceRecord;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Logs;
use App\Models\PengurusKelas;
use App\Models\PresensiSiswa;
use App\Models\Role;
use App\Models\Siswa;
use App\Services\AttendanceEvidenceStorage;
use App\Services\AttendanceService;
use App\Services\PresensiFilterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class WaliKelasController extends Controller
{
    public function __construct(
        private PresensiFilterService $presensiFilterService,
        private AttendanceService $attendanceService,
        private AttendanceEvidenceStorage $evidenceStorage,
    ) {}

    public function index()
    {
        $user = Auth::user()->id_akun;

        $totalSiswa = Siswa::query()
            ->selectRaw('COUNT(*) as totalSiswa')
            ->joinKelasGuruWali()
            ->where('guru.id_akun', $user)
            ->value('totalSiswa');

        $attendanceStats = PresensiSiswa::query()
            ->selectRaw('COALESCE(SUM(CASE WHEN presensi_siswa.status_kehadiran = ? THEN 1 ELSE 0 END), 0) as totalHadir', ['hadir'])
            ->selectRaw('COALESCE(SUM(CASE WHEN presensi_siswa.status_kehadiran = ? THEN 1 ELSE 0 END), 0) as totalIzin', ['izin'])
            ->selectRaw('COALESCE(SUM(CASE WHEN presensi_siswa.status_kehadiran = ? THEN 1 ELSE 0 END), 0) as totalAlpha', ['alpha'])
            ->joinSiswaKelasGuruWali()
            ->where('guru.id_akun', $user)
            ->first();

        $totalHadir = $attendanceStats->totalHadir ?? 0;
        $totalIzin = $attendanceStats->totalIzin ?? 0;
        $totalAlpha = $attendanceStats->totalAlpha ?? 0;

        return view('wali-kelas.index', compact('totalSiswa', 'totalHadir', 'totalIzin', 'totalAlpha'));
    }

    public function detailProfil(Request $request, Guru $guru, Kelas $kelas)
    {
        $guruRecord = $guru->where('id_akun', $request->id)->first();
        if ($guruRecord === null) {
            return view('layout.profile-unavailable', [
                'message' => 'Akun wali kelas ini belum terhubung dengan data guru.',
                'backUrl' => route('wali-kelas.dashboard'),
            ]);
        }

        $id_guru = $guruRecord->id_guru;
        $data = [
            'guru' => $guru
                ->join('akun', 'guru.id_akun', '=', 'akun.id_akun')
                ->where('id_guru', $id_guru)->first(),
            'kelas' => $kelas
                ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
                ->where('id_wali_kelas', $id_guru)
                ->orderBy('tingkatan')->get(),
        ];

        return view('wali-kelas.detail-profil', $data);
    }

    public function showSiswa(Request $request)
    {
        $user = Auth::user()->id_akun;

        $filter = DB::table('view_siswa')
            ->join('kelas', 'view_siswa.id_kelas', '=', 'kelas.id_kelas')
            ->join('guru', 'guru.id_guru', '=', 'kelas.id_wali_kelas')
            ->where('guru.id_akun', $user)
            ->where(function ($query) use ($request) {
                $query->where('nis', 'LIKE', "%$request->keyword%")
                    ->orWhere('view_siswa.nama_siswa', 'LIKE', "%$request->keyword%")
                    ->orWhere('view_siswa.jenis_kelamin', 'LIKE', "%$request->keyword%")
                    ->orWhere('view_siswa.tingkatan', 'LIKE', "%$request->keyword%")
                    ->orWhere('view_siswa.nama_jurusan', 'LIKE', "%$request->keyword%");
            });

        if ($request->filter_jenkel != null) {
            $filter->where('jenis_kelamin', $request->filter_jenkel);
        }

        if ($request->filter_tingkatan != null) {
            $filter->where('tingkatan', $request->filter_tingkatan);
        }

        if ($request->filter_jurusan != null) {
            $filter->where('jurusan.id_jurusan', $request->filter_jurusan);
        }

        $data = [
            'siswa' => $filter->simplePaginate(25)->withQueryString(),
        ];

        return view('wali-kelas.siswa', $data);
    }

    public function showPengurus(PengurusKelas $pengurus, Request $request)
    {
        $user = Auth::user()->id_akun;

        $filter = $pengurus
            ->join('siswa', 'siswa.id_siswa', '=', 'pengurus_kelas.id_siswa')
            ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->select('siswa.nama_siswa', 'siswa.status_jabatan', 'kelas.nama_kelas', 'siswa.id_siswa', 'siswa.nis', 'pengurus_kelas.id_pengurus')
            ->join('guru', 'guru.id_guru', '=', 'kelas.id_wali_kelas')
            ->where('guru.id_akun', $user)
            ->where(function ($query) {
                $query->where('siswa.status_jabatan', 'ketua_kelas')
                    ->orWhere('siswa.status_jabatan', 'wakil_kelas')
                    ->orWhere('siswa.status_jabatan', 'sekretaris');
            });

        if ($request->filled('keyword')) {
            $filter->where(function ($query) use ($request) {
                $query
                    ->where('siswa.nama_siswa', 'LIKE', "%$request->keyword%")
                    ->orWhere('siswa.nis', 'LIKE', "%$request->keyword%");
            });
        }

        if ($request->filter_jabatan != null) {
            $filter = $filter->where('status_jabatan', $request->filter_jabatan);
        }

        $data = [
            'pengurus' => $filter->simplePaginate(25)->withQueryString(),
        ];

        return view('wali-kelas.pengurus-kelas', $data);
    }

    public function showPresensi(PresensiSiswa $presensi, Request $request)
    {
        $user = Auth::user()->id_akun;
        $query = $presensi
            ->joinSiswaKelasGuruWaliJurusan()
            ->select(
                'presensi_siswa.id_presensi',
                'siswa.nis',
                'siswa.nama_siswa',
                'presensi_siswa.tanggal',
                'kelas.tingkatan',
                'jurusan.nama_jurusan',
                'kelas.nama_kelas',
                'presensi_siswa.status_kehadiran',
                'presensi_siswa.foto_bukti',
                'presensi_siswa.keterangan'
            )
            ->where('guru.id_akun', $user);
        if (Schema::hasTable('attendance_records')) {
            $query
                ->leftJoin('attendance_records', 'attendance_records.legacy_presensi_id', '=', 'presensi_siswa.id_presensi')
                ->addSelect(
                    'attendance_records.id as attendance_record_id',
                    'attendance_records.evidence_disk',
                    'attendance_records.evidence_path',
                    'attendance_records.evidence_mime',
                );
        }

        $filter = $this->presensiFilterService->filter($request, $query, true, [
            'search_columns' => ['nama_siswa', 'tanggal', 'status_kehadiran', 'nama_kelas'],
            'month_filter' => null,
            'kelas_filter' => null,
        ]);

        $data = [
            'presensi' => $filter,
        ];

        return view('wali-kelas.presensi', $data);
    }

    public function detailSiswa(Request $request, Kelas $kelas, Siswa $siswa, PengurusKelas $pengurus)
    {
        $data = [
            'siswa' => $siswa->where('id_siswa', $request->id)
                ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
                ->join('akun', 'siswa.id_akun', '=', 'akun.id_akun')
                ->first(),
            'kelas' => $kelas
                ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
                ->first(),
            'pengurus' => $pengurus
                ->join('siswa', 'siswa.id_siswa', '=', 'pengurus_kelas.id_siswa')
                ->where('siswa.id_siswa', $request->id)
                ->first(),

        ];

        return view('wali-kelas.detail-siswa', $data);
    }

    public function detailKelasPengurus(Request $request, Kelas $kelas, Siswa $siswa, PengurusKelas $pengurus)
    {
        $user = Auth::user()->id_akun;
        $data = [
            'pengurus' => $pengurus
                ->join('siswa', 'pengurus_kelas.id_siswa', '=', 'siswa.id_siswa')
                ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
                ->join('guru', 'guru.id_guru', '=', 'kelas.id_wali_kelas')
                ->where('kelas.id_kelas', $request->id)
                ->where('guru.id_akun', $user)
                ->select('pengurus_kelas.id_pengurus', 'siswa.id_siswa', 'siswa.nis', 'siswa.nama_siswa', 'siswa.status_jabatan', 'kelas.nama_kelas')
                ->simplePaginate(25)
                ->withQueryString(),
        ];

        return view('wali-kelas.pengurus-kelas', $data);
    }

    public function createPengurus(Siswa $siswa)
    {
        $user = Auth::user()->id_akun;

        $data = [
            'siswa' => $siswa
                ->join('akun', 'siswa.id_akun', '=', 'akun.id_akun')
                ->select('siswa.id_siswa', 'siswa.nama_siswa', 'akun.id_role')
                ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
                ->join('guru', 'guru.id_guru', '=', 'kelas.id_wali_kelas')
                ->leftJoin('pengurus_kelas', 'siswa.id_siswa', '=', 'pengurus_kelas.id_siswa')
                ->where('guru.id_akun', $user)
                ->whereNull('pengurus_kelas.jabatan')
                ->where(function ($query) {
                    $query->orWhere('siswa.status_jabatan', '=', 'ketua_kelas')
                        ->orWhere('siswa.status_jabatan', '=', 'wakil_kelas');
                })
                ->get(),
        ];

        return view('wali-kelas.tambah-pengurus', $data);
    }

    public function storePengurus(Request $request, PengurusKelas $pengurus, Role $role, Akun $akun)
    {
        $data = $request->validate([
            'id_siswa' => 'required',
        ]);

        $user = Auth::user();
        $role_akun = $role->where('id_role', $user->id_role)->first('nama_role');
        $data['pembuat'] = $role_akun->nama_role;
        $data['jabatan'] = 'Pengurus Kelas';

        $pengurus->create($data);

        $siswaId = $request->input('id_siswa');
        $akun->join('siswa', 'akun.id_akun', '=', 'siswa.id_akun')
            ->where('siswa.id_siswa', $siswaId)
            ->update(['akun.id_role' => 3]);

        notify()->success('Data pengurus kelas telah ditambah', 'Success');

        return redirect('wali-kelas/akun-pengurus-kelas')->with('success', 'Data pengurus kelas berhasil ditambah');
    }

    public function editSiswa(Request $request, Kelas $kelas, Siswa $siswa)
    {
        $jenisKelamin = ['laki-laki', 'perempuan'];
        $statusJabatan = ['sekretaris', 'ketua_kelas', 'wakil_kelas', 'bendahara', 'siswa'];

        $data = [
            'siswa' => $siswa->where('id_siswa', $request->id)
                ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
                ->join('akun', 'siswa.id_akun', '=', 'akun.id_akun')
                ->first(),
            'kelas' => $kelas
                ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
                ->get(),
            'jenisKelamin' => $jenisKelamin,
            'statusJabatan' => $statusJabatan,

        ];

        return view('wali-kelas.edit-siswa', $data);
    }

    public function editPengurus(Request $request, Kelas $kelas, PengurusKelas $pengurus)
    {
        $pengurus = [
            'pengurus' => $pengurus->join('siswa', 'pengurus_kelas.id_siswa', '=', 'siswa.id_siswa')
                ->where('id_pengurus', '=', $request->id)
                ->first(),
        ];

        return view('wali-kelas.edit-pengurus', $pengurus);
    }

    public function editPresensi(Request $request, Kelas $kelas, PresensiSiswa $presensi)
    {
        $statusKehadiran = ['hadir', 'izin', 'alpha'];

        $data = [
            'presensi' => $presensi->where('id_presensi', $request->id)->first(),
            'kelas' => $kelas
                ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
                ->get(),
            'statusKehadiran' => $statusKehadiran,
        ];

        return view('wali-kelas.edit-presensi', $data);
    }

    public function updateSiswa(Request $request, Siswa $siswa, Role $role)
    {
        $id_siswa = $request->input('id_siswa');

        $data = $request->validate([
            'nis' => 'sometimes',
            'nama_siswa' => 'sometimes|string|regex:/^[^\d]+$/',
            'id_kelas' => 'sometimes',
            'jenis_kelamin' => 'sometimes',
            'nomer_hp' => 'sometimes',
            'status_jabatan' => 'sometimes',
            'foto_siswa' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'nama_siswa.regex' => 'Nama siswa hanya boleh huruf',
            'foto_siswa.mimes' => 'Foto siswa harus berextensi jpg, jpeg, png',
            'foto_siswa.uploaded' => 'Foto siswa gagal di upload.',
        ]);

        $user = Auth::user();
        $role_akun = $role->where('id_role', $user->id_role)->first('nama_role');
        $data['pembuat'] = $role_akun->nama_role;

        if ($id_siswa !== null) {
            if ($request->hasFile('foto_siswa') && $request->file('foto_siswa')->isValid()) {
                $foto_file = $request->file('foto_siswa');
                $foto_extension = $foto_file->getClientOriginalExtension();
                $foto_nama = Str::uuid().'.'.$foto_extension;
                $foto_file->move(public_path('siswa'), $foto_nama);

                $update_data = $siswa->where('id_siswa', $id_siswa)->first();
                $old_file_path = public_path('siswa').'/'.$update_data->foto_siswa;

                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }

                $data['foto_siswa'] = $foto_nama;
            }

            $dataUpdate = $siswa->where('id_siswa', $id_siswa)->update($data);

            if ($dataUpdate) {
                notify()->success('Data siswa telah diperbarui', 'Success');

                return redirect('wali-kelas/akun-siswa')->with('success', 'Data berhasil diupdate');
            }
        }

        return back()->with('error', 'Data gagal diupdate');
    }

    public function updatePengurus(Request $request, PengurusKelas $pengurus, Role $role)
    {
        $id_pengurus = $request->input('id_pengurus');
        $data = $request->validate([
            'id_pengurus' => 'required',
            'jabatan' => 'required',
        ]);

        $user = Auth::user();
        $role_akun = $role->where('id_role', $user->id_role)->first('nama_role');
        $data['pembuat'] = $role_akun->nama_role;

        if ($pengurus->where('id_pengurus', $id_pengurus)->update($data)) {
            notify()->success('Data pengurus kelas telah diperbarui', 'Success');

            return redirect('/wali-kelas/akun-pengurus-kelas');
        }

        return back()->with('error', 'Data pengurus gagal ditambahkan');
    }

    public function updatePresensi(Request $request, PresensiSiswa $presensi, Role $role)
    {
        $idPresensi = $request->input('id_presensi');
        $data = $request->validate([
            'id_siswa' => ['required', 'integer'],
            'status_kehadiran' => ['required', 'in:hadir,izin,alpha'],
            'keterangan' => ['required', 'string', 'max:2000'],
            'foto_bukti' => ['sometimes', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ], [
            'keterangan.required' => 'Keterangan harus diisi',
            'foto_bukti.uploaded' => 'Foto bukti gagal di upload.',
        ]);

        $user = Auth::user();
        $legacy = $idPresensi === null
            ? null
            : $presensi->where('id_presensi', $idPresensi)->first();
        if ($legacy === null || (int) $legacy->id_siswa !== (int) $data['id_siswa']) {
            return back()->with('error', 'Data presensi tidak ditemukan.');
        }

        $existingRecord = AttendanceRecord::query()
            ->where('legacy_presensi_id', $legacy->getKey())
            ->first();
        $oldEvidence = $existingRecord === null ? null : [
            'disk' => $existingRecord->evidence_disk,
            'path' => $existingRecord->evidence_path,
        ];
        $evidence = null;

        try {
            if ($request->hasFile('foto_bukti')) {
                $evidence = $this->evidenceStorage->storeUploadedFile($request->file('foto_bukti'));
            }

            DB::transaction(function () use ($user, $role, $legacy, $data, $evidence): void {
                $roleLabel = $role->where('id_role', $user->id_role)->value('nama_role') ?: 'Wali Kelas';
                $legacy->update([
                    'status_kehadiran' => $data['status_kehadiran'],
                    'keterangan' => $data['keterangan'],
                    'pembuat' => $roleLabel,
                    ...(isset($evidence) ? ['foto_bukti' => ''] : []),
                ]);

                $this->attendanceService->synchronizeLegacyRecord(
                    $user,
                    $legacy->refresh(),
                    $data['status_kehadiran'],
                    $data['keterangan'],
                    $evidence ?? [],
                );
            });
        } catch (Throwable $exception) {
            if ($evidence !== null) {
                $this->evidenceStorage->delete($evidence['path'], $evidence['disk']);
            }

            report($exception);

            return back()->withInput()->with('error', 'Data gagal diperbarui.');
        }

        if ($evidence !== null && $oldEvidence !== null
            && $oldEvidence['path'] !== null
            && $oldEvidence['path'] !== $evidence['path']) {
            $this->evidenceStorage->delete($oldEvidence['path'], $oldEvidence['disk']);
        }

        notify()->success('Data presensi siswa telah diperbarui', 'Success');

        return redirect('wali-kelas/presensi-siswa');
    }

    public function destroyPengurus(Request $request, Akun $akun)
    {
        $id_pengurus = $request->input('id_pengurus');

        $siswaId = PengurusKelas::where('id_pengurus', $id_pengurus)->value('id_siswa');

        $aksi = PengurusKelas::where('id_pengurus', $id_pengurus)->delete();

        if ($aksi) {
            $akun->join('siswa', 'akun.id_akun', '=', 'siswa.id_akun')
                ->where('siswa.id_siswa', $siswaId)
                ->update(['akun.id_role' => 1]);

            $pesan = [
                'success' => true,
                'pesan' => 'Data berhasil dihapus',
            ];
        } else {
            $pesan = [
                'success' => false,
                'pesan' => 'Data gagal dihapus',
            ];
        }

        return response()->json($pesan);
    }

    public function logs(Logs $logs)
    {
        $data = [
            'logs' => $logs::orderBy('id_log', 'desc')->get(),

        ];

        return view('wali-kelas.logs', $data);
    }

    public function exportPresensi(Request $request, PresensiSiswa $presensi)
    {
        $user = Auth::user()->id_akun;
        $query = $presensi
            ->joinSiswaKelasGuruWaliJurusan()
            ->select(
                'presensi_siswa.id_presensi',
                'siswa.nis',
                'siswa.nama_siswa',
                'presensi_siswa.tanggal',
                'kelas.tingkatan',
                'jurusan.nama_jurusan',
                'kelas.nama_kelas',
                'presensi_siswa.status_kehadiran',
                'presensi_siswa.foto_bukti',
                'presensi_siswa.keterangan'
            )
            ->where('guru.id_akun', $user);
        if (Schema::hasTable('attendance_records')) {
            $query
                ->leftJoin('attendance_records', 'attendance_records.legacy_presensi_id', '=', 'presensi_siswa.id_presensi')
                ->addSelect(
                    'attendance_records.id as attendance_record_id',
                    'attendance_records.evidence_disk',
                    'attendance_records.evidence_path',
                    'attendance_records.evidence_mime',
                );
        }

        $filter = $this->presensiFilterService->filter($request, $query, false, [
            'search_columns' => ['nama_siswa', 'tanggal', 'status_kehadiran', 'nama_kelas'],
            'month_filter' => null,
            'kelas_filter' => null,
        ]);
        $pdf = Pdf::loadView('wali-kelas.presensi-pdf', ['presensi' => $filter]);

        return $pdf->download('presensi.pdf');
    }
}
