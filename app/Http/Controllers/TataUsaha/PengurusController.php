<?php

namespace App\Http\Controllers\TataUsaha;

use App\Http\Controllers\Controller;
use App\Models\Akun;
use App\Models\Kelas;
use App\Models\PengurusKelas;
use App\Models\Role;
use App\Models\Siswa;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PengurusController extends Controller
{
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

        return view('tata-usaha.pengurus-kelas', $data);
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

        return view('tata-usaha.detail-pengurus', $data);
    }

    public function createPengurus(Siswa $siswa, Request $request)
    {
        $data = $siswa->where('status_jabatan', 'siswa');
        if ($request->kelas != null) {
            $data->where('id_kelas', $request->kelas);
        }

        return view('tata-usaha.tambah-pengurus', ['siswa' => $data->get()]);
    }

    public function storePengurus(Request $request, PengurusKelas $pengurus, Role $role, Siswa $siswa, Akun $akun)
    {
        $data = $request->validate([
            'id_siswa' => 'required',
            'status_jabatan' => 'required',
        ]);

        $data_pengurus = [
            'id_siswa' => $data['id_siswa'],
            'jabatan' => 'Pengurus Kelas',
        ];
        $user = Auth::user();
        $role_akun = $role->where('id_role', $user->id_role)->first('nama_role');
        $data_pengurus['pembuat'] = $role_akun->nama_role;

        try {
            $siswa->where('id_siswa', $data['id_siswa'])->update(['status_jabatan' => $data['status_jabatan']]);
            $akun->where('id_akun', $siswa->where('id_siswa', $data['id_siswa'])->first()->id_akun)->update(['id_role' => 3]);
            $pengurus->create($data_pengurus);
            notify()->success('Data pengurus kelas telah berhasil ditambahkan', 'Success');

            return redirect('tata-usaha/akun-pengurus-kelas');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function editPengurus(Request $request, Kelas $kelas, PengurusKelas $pengurus)
    {
        $pengurus = [
            'pengurus' => $pengurus->join('siswa', 'pengurus_kelas.id_siswa', '=', 'siswa.id_siswa')
                ->where('id_pengurus', '=', $request->id)
                ->first(),
        ];

        return view('tata-usaha.edit-pengurus', $pengurus);
    }

    public function updatePengurus(Request $request, PengurusKelas $pengurus, Role $role, Siswa $siswa)
    {
        $id_pengurus = $request->input('id_pengurus');
        $data = $request->validate([
            'status_jabatan' => 'required',
        ]);

        $user = Auth::user();
        $pembuat = $role->where('id_role', $user->id_role)->first('nama_role')->nama_role;

        try {
            $siswa->where('id_siswa', $pengurus->where('id_pengurus', $id_pengurus)->first()->id_siswa)->update([
                'status_jabatan' => $data['status_jabatan'],
            ]);
            $pengurus->where('id_pengurus', $id_pengurus)->update([
                'pembuat' => $pembuat,
            ]);
            notify()->success('Data pengurus kelas telah berhasil diupdate', 'Success');

            return redirect('/tata-usaha/akun-pengurus-kelas');
        } catch (Exception $e) {
            return back()->with('error', 'Data pengurus gagal ditambahkan');
        }
    }

    public function destroyPengurus(Request $request, Role $role, PengurusKelas $pengurus, Siswa $siswa, Akun $akun)
    {
        $id_pengurus = $request->input('id_pengurus');
        $user = Auth::user();
        $role_akun = $role->where('id_role', $user->id_role)->first('nama_role');
        $data['pembuat'] = $role_akun->nama_role;
        try {
            $id_siswa = $pengurus->where('id_pengurus', $id_pengurus)->first()->id_siswa;
            $siswa->where('id_siswa', $id_siswa)->update(['status_jabatan' => 'siswa']);
            $id_akun = $siswa->where('id_siswa', $id_siswa)->first()->id_akun;
            $akun->where('id_akun', $id_akun)->update(['id_role' => 1]);
            $pengurus->where('id_pengurus', $id_pengurus)->update($data);
            $pengurus->where('id_pengurus', $id_pengurus)->delete();
            $pesan = [
                'success' => true,
                'pesan' => 'Data berhasil di hapus',
            ];
        } catch (Exception $e) {
            $pesan = [
                'success' => false,
                'pesan' => 'Data gagal di hapus',
                'error' => $e->getMessage(),
            ];
        }

        return response()->json($pesan);
    }
}
