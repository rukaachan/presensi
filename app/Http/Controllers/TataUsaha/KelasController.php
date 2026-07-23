<?php

namespace App\Http\Controllers\TataUsaha;

use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Role;
use App\Models\Siswa;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KelasController extends Controller
{
    public function showKelas(Kelas $kelas, Jurusan $jurusan, Request $request)
    {
        $filter = $kelas->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
            ->where(function ($query) use ($request) {
                $query->where('tingkatan', 'LIKE', "%$request->keyword%")
                    ->orWhere('nama_jurusan', 'LIKE', "%$request->keyword%")
                    ->orWhere('nama_kelas', 'LIKE', "%$request->keyword%")
                    ->orWhere('status_kelas', 'LIKE', "%$request->keyword%");
            });
        if ($request->filter_tingkatan != null) {
            $filter = $filter->where('tingkatan', $request->filter_tingkatan);
        }

        if ($request->filter_jurusan != null) {
            $filter = $filter->where('jurusan.id_jurusan', $request->filter_jurusan);
        }

        if ($request->filter_status != null) {
            $filter = $filter->where('status_kelas', $request->filter_status);
        }

        $data = [
            'kelas' => $filter->orderBy('tingkatan', 'asc')->get(),
            'jurusan' => $jurusan->get(),
        ];

        return view('tata-usaha.kelas', $data);
    }

    public function detailKelas(Request $request, Kelas $kelas, Siswa $siswa)
    {
        $data = [
            'kelas' => $kelas
                ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
                ->join('guru', 'kelas.id_wali_kelas', '=', 'guru.id_guru')
                ->where('id_kelas', $request->id)->first(),
            'ketua' => $siswa
                ->where('id_kelas', $request->id)
                ->where('status_jabatan', 'ketua_kelas')->get(),
            'wakil' => $siswa
                ->where('id_kelas', $request->id)
                ->where('status_jabatan', 'wakil_kelas')->get(),
            'bendahara' => $siswa
                ->where('id_kelas', $request->id)
                ->where('status_jabatan', 'bendahara')->get(),
            'sekretaris' => $siswa
                ->where('id_kelas', $request->id)
                ->where('status_jabatan', 'sekretaris')->get(),
            'siswa' => $siswa
                ->where('id_kelas', $request->id)
                ->leftJoin('pengurus_kelas', 'pengurus_kelas.id_siswa', '=', 'siswa.id_siswa')
                ->select('siswa.id_siswa as id_siswa', 'id_pengurus', 'foto_siswa', 'nis', 'nama_siswa', 'jenis_kelamin', 'status_jabatan', 'status_siswa')
                ->get(),
        ];

        return view('tata-usaha.detail-kelas', $data);
    }

    public function createKelas(Jurusan $jurusan)
    {
        return view('tata-usaha.tambah-kelas', ['jurusan' => $jurusan->get()]);
    }

    public function storeKelas(Kelas $kelas, Request $request, Role $role)
    {
        $data = $request->validate([
            'tingkatan' => 'required',
            'id_jurusan' => 'required',
            'nama_kelas' => 'required',
            'status_kelas' => 'required',
        ]);

        $user = Auth::user();
        $role_akun = $role->where('id_role', $user->id_role)->first('nama_role');
        $data['pembuat'] = $role_akun->nama_role;

        $kelas->create($data);
        notify()->success('Data kelas telah berhasil ditambahkan', 'Success');

        return redirect('tata-usaha/kelas?filter_status=aktif');
    }

    public function editKelas(Kelas $kelas, Jurusan $jurusan, Request $request)
    {
        $data = [
            'kelas' => $kelas->where('id_kelas', $request->id)->first(),
            'jurusan' => $jurusan->get(),
        ];

        return view('tata-usaha.edit-kelas', $data);
    }

    public function updateKelas(Kelas $kelas, Request $request, Role $role)
    {
        $id_kelas = $request->input('id_kelas');
        $data = $request->validate([
            'tingkatan' => 'required',
            'id_jurusan' => 'required',
            'nama_kelas' => 'required',
            'status_kelas' => 'required',
        ]);

        $user = Auth::user();
        $role_akun = $role->where('id_role', $user->id_role)->first('nama_role');
        $data['pembuat'] = $role_akun->nama_role;

        try {
            if ($kelas->where('id_kelas', $id_kelas)->update($data)) {
                notify()->success('Data kelas telah berhasil diupdate', 'Success');
            }

            return redirect('tata-usaha/kelas');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function destroyKelas(Kelas $kelas, Request $request, Role $role, Siswa $siswa)
    {
        $id_kelas = $request->input('id_kelas');

        $user = Auth::user();
        $role_akun = $role->where('id_role', $user->id_role)->first('nama_role')->nama_role;

        $kelas->where('id_kelas', $id_kelas)->update(['pembuat' => $role_akun]);

        if ($kelas->where('id_kelas', $id_kelas)->delete()) {
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
}
