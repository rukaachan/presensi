<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\PengurusKelas;
use App\Models\PresensiSiswa;
use App\Models\Role;
use App\Services\PresensiFilterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GuruPiketController extends Controller
{
    public function __construct(private PresensiFilterService $presensiFilterService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $statusTotals = DB::selectOne("\n            SELECT\n                COALESCE(SUM(CASE WHEN status_kehadiran = 'hadir' THEN 1 ELSE 0 END), 0) AS totalHadir,\n                COALESCE(SUM(CASE WHEN status_kehadiran = 'izin' THEN 1 ELSE 0 END), 0) AS totalIzin,\n                COALESCE(SUM(CASE WHEN status_kehadiran = 'alpha' THEN 1 ELSE 0 END), 0) AS totalAlpha\n            FROM presensi_siswa\n        ");

        $totalHadir = $statusTotals->totalHadir;
        $totalIzin = $statusTotals->totalIzin;
        $totalAlpha = $statusTotals->totalAlpha;

        return view('guru-piket.index', compact('totalHadir', 'totalIzin', 'totalAlpha'));
    }

    public function detailProfil(Request $request, Guru $guru)
    {
        $id_guru = $guru->where('id_akun', $request->id)->first()->id_guru;
        $data = [
            'guru' => $guru
                ->join('akun', 'guru.id_akun', '=', 'akun.id_akun')
                ->where('id_guru', $id_guru)->first(),
        ];

        return view('guru-piket.detail-profil', $data);
    }

    public function showPengurus(PengurusKelas $pengurus, Kelas $kelas, Request $request)
    {
        $filter = $pengurus
            ->join('siswa', 'siswa.id_siswa', '=', 'pengurus_kelas.id_siswa')
            ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
            ->where(function ($query) use ($request) {
                $query->where('nama_siswa', 'LIKE', "%$request->keyword%")
                    ->orWhere('nis', 'LIKE', "%$request->keyword%")
                    ->orWhere('jabatan', 'LIKE', "%$request->keyword%")
                    ->orWhere('nama_kelas', 'LIKE', "%$request->keyword%")
                    ->orWhere('tingkatan', 'LIKE', "%$request->keyword%");
            });

        if ($request->filter_jabatan != null) {
            $filter = $filter->where('status_jabatan', $request->filter_jabatan);
        }

        if ($request->filter_kelas != null) {
            $filter = $filter->where('kelas.id_kelas', $request->filter_kelas);
        }

        $data = [
            'pengurus' => $filter->simplePaginate(25)->withQueryString(),
            'kelas' => $kelas->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')->orderBy('tingkatan')->orderBy('nama_kelas')->get(),
        ];

        return view('guru-piket.pengurus-kelas', $data);
    }

    public function detailPengurus(Request $request, PengurusKelas $pengurus)
    {
        $data = [
            'pengurus' => $pengurus
                ->join('siswa', 'pengurus_kelas.id_siswa', '=', 'siswa.id_siswa')
                ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
                ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
                ->where('id_pengurus', $request->id)->first(),
        ];

        return view('guru-piket.detail-pengurus', $data);
    }

    public function showPresensi(Request $request, Kelas $kelas, PresensiSiswa $presensi)
    {
        $query = $this->presensiFilterService->buildBaseQuery($presensi);
        $filter = $this->presensiFilterService->filter($request, $query);
        $data = [
            'presensi' => $filter,
            'kelas' => $kelas->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')->orderBy('tingkatan')->orderBy('nama_kelas')->get(),
        ];

        return view('guru-piket.presensi', $data);
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

        return view('guru-piket.detail-presensi', $data);
    }

    public function editPresensi(Request $request, PresensiSiswa $presensi)
    {
        $statusKehadiran = ['hadir', 'izin', 'alpha'];

        $data = [
            'presensi' => $presensi->where('id_presensi', $request->id)->first(),
            'statusKehadiran' => $statusKehadiran,
        ];

        return view('guru-piket.edit-presensi', $data);
    }

    public function updatePresensi(Request $request, Role $role, PresensiSiswa $presensi)
    {
        $id_presensi = $request->input('id_presensi');

        $data = $request->validate([
            'status_kehadiran' => 'required',
            'keterangan' => 'required',
            'foto_bukti' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();
        $role_akun = $role->where('id_role', $user->id_role)->first('nama_role');
        $data['pembuat'] = $role_akun->nama_role;
        if ($id_presensi !== null) {
            if ($request->hasFile('foto_bukti') && $request->file('foto_bukti')->isValid()) {
                $foto_file = $request->file('foto_bukti');
                $foto_extension = $foto_file->getClientOriginalExtension();
                $foto_nama = md5($foto_file->getClientOriginalName().time()).'.'.$foto_extension;
                $foto_file->move(public_path('presensi_bukti'), $foto_nama);

                $update_data = $presensi->where('id_presensi', $id_presensi)->first();
                $old_file_path = public_path('presensi_bukti').'/'.$update_data->foto_bukti;

                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }

                $data['foto_bukti'] = $foto_nama;
            }

            $dataUpdate = $presensi->where('id_presensi', $id_presensi)->update($data);

            if ($dataUpdate) {
                notify()->success('Data presensi siswa telah diperbarui', 'Success');

                return redirect('guru-piket/presensi');
            }
        }

        return back()->with('error', 'Data gagal diperbarui');
    }

    public function exportPresensi(Request $request, PresensiSiswa $presensi)
    {
        $query = $this->presensiFilterService->buildBaseQuery($presensi);
        $filter = $this->presensiFilterService->filter($request, $query, false);
        $pdf = Pdf::loadView('presensi-pdf', ['presensi' => $filter]);

        return $pdf->download('presensi.pdf');
    }
}
