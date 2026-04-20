<?php

namespace App\Http\Controllers\TataUsaha;

use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Role;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JurusanController extends Controller
{
    public function showJurusan(Jurusan $jurusan, Request $request)
    {
        $data = [
            'jurusan' => $jurusan->where('nama_jurusan', 'LIKE', "%$request->keyword%")->get(),
        ];

        return view('tata-usaha.jurusan', $data);
    }

    public function createJurusan()
    {
        return view('tata-usaha.tambah-jurusan');
    }

    public function storeJurusan(Jurusan $jurusan, Request $request, Role $role)
    {
        $data = $request->validate([
            'nama_jurusan' => 'required',
        ]);

        $user = Auth::user();
        $role_akun = $role->where('id_role', $user->id_role)->first('nama_role');
        $data['pembuat'] = $role_akun->nama_role;

        $jurusan->create($data);
        notify()->success('Data jurusan telah berhasil ditambahkan', 'Success');

        return redirect('tata-usaha/jurusan');
    }

    public function editJurusan(Jurusan $jurusan, Request $request)
    {
        $data = $jurusan->where('id_jurusan', $request->id)->first();

        return view('tata-usaha.edit-jurusan', ['data' => $data]);
    }

    public function updateJurusan(Jurusan $jurusan, Request $request, Role $role)
    {
        $id_jurusan = $request->input('id_jurusan');
        $data = $request->validate([
            'nama_jurusan' => 'required',
        ]);

        $user = Auth::user();
        $role_akun = $role->where('id_role', $user->id_role)->first('nama_role');
        $data['pembuat'] = $role_akun->nama_role;

        try {
            if ($jurusan->where('id_jurusan', $id_jurusan)->update($data)) {
                notify()->success('Data jurusan telah berhasil diupdate', 'Success');
            }

            return redirect('tata-usaha/jurusan');
        } catch (Exception $e) {
            return back()->with('error', 'Data jurusan gagal diupdate');
        }
    }

    public function destroyJurusan(Jurusan $jurusan, Request $request, Role $role, Kelas $kelas)
    {
        $id_jurusan = $request->input('id_jurusan');

        $user = Auth::user();
        $pembuat = $role->where('id_role', $user->id_role)->first('nama_role')->nama_role;

        $jurusan->where('id_jurusan', $id_jurusan)->update(['pembuat' => $pembuat]);

        if ($jurusan->where('id_jurusan', $id_jurusan)->delete()) {
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
