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
use Illuminate\Support\Facades\Hash;

class SiswaController extends Controller
{
    public function showSiswa(Siswa $siswa, Kelas $kelas, Request $request)
    {
        $filter = $siswa
            ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
            ->where(function ($query) use ($request) {
                $query->where('nis', 'LIKE', "%$request->keyword%")
                    ->orWhere('nama_siswa', 'LIKE', "%$request->keyword%")
                    ->orWhere('jenis_kelamin', 'LIKE', "%$request->keyword%")
                    ->orWhere('tingkatan', 'LIKE', "%$request->keyword%")
                    ->orWhere('nama_jurusan', 'LIKE', "%$request->keyword%")
                    ->orWhere('status_kelas', 'LIKE', "%$request->keyword%")
                    ->orWhere('nama_kelas', 'LIKE', "%$request->keyword%");
            });

        if ($request->filter_jenkel != null) {
            $filter->where('jenis_kelamin', $request->filter_jenkel);
        }

        if ($request->filter_kelas != null) {
            $filter->where('kelas.id_kelas', $request->filter_kelas);
        }

        if ($request->filter_status != null) {
            $filter = $filter->where('status_siswa', $request->filter_status);
        }

        $data = [
            'siswa' => $filter->simplePaginate(25)->withQueryString(),
            'kelas' => $kelas->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')->orderBy('tingkatan')->orderBy('nama_kelas')->get(),
        ];

        return view('tata-usaha.siswa', $data);
    }

    public function detailSiswa(Request $request, Siswa $siswa, PengurusKelas $pengurus)
    {
        $data = [
            'siswa' => $siswa
                ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
                ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
                ->where('id_siswa', $request->id)->first(),
            'pengurus' => $pengurus->where('id_siswa', $request->id)->first(),
        ];

        return view('tata-usaha.detail-siswa', $data);
    }

    public function createSiswa(Kelas $kelas, Siswa $siswa)
    {
        $jenisKelamin = ['laki-laki', 'perempuan'];

        $kelas = $kelas
            ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
            ->orderBy('tingkatan')
            ->get();

        $siswa->all();

        return view('tata-usaha.tambah-siswa', ['kelas' => $kelas, 'jenisKelamin' => $jenisKelamin, 'siswa' => $siswa]);
    }

    public function storeSiswa(Request $request, Siswa $siswa, Role $role, Akun $akun)
    {
        $data = $request->validate([
            'nis' => 'required',
            'nama_siswa' => 'required',
            'id_kelas' => 'required',
            'jenis_kelamin' => 'required',
            'nomer_hp' => 'required',
            'angkatan' => 'required',
            'username' => 'required',
            'password' => 'required',
            'foto_siswa' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);
        $data_siswa = [
            'nis' => $data['nis'],
            'nama_siswa' => $data['nama_siswa'],
            'id_kelas' => $data['id_kelas'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'nomer_hp' => $data['nomer_hp'],
            'angkatan' => $data['angkatan'],
            'status_siswa' => 'aktif',
        ];

        $data_siswa['status_jabatan'] = 'siswa';
        $id_akun = $akun->create([
            'id_role' => 1,
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
        ]);

        $data_siswa['id_akun'] = $id_akun->id_akun;

        $user = Auth::user();
        $role_akun = $role->where('id_role', $user->id_role)->first('nama_role');
        $data_siswa['pembuat'] = $role_akun->nama_role;

        if ($request->hasFile('foto_siswa') && $request->file('foto_siswa')->isValid()) {
            $foto_file = $request->file('foto_siswa');
            $foto_nama = md5($foto_file->getClientOriginalName().time()).'.'.$foto_file->getClientOriginalExtension();
            $foto_file->move(public_path('siswa'), $foto_nama);
            $data_siswa['foto_siswa'] = $foto_nama;
        } else {
            return back()->withInput()->with('error', 'Foto siswa gagal diunggah.');
        }

        $siswa->create($data_siswa);
        notify()->success('Data siswa telah berhasil ditambahkan', 'Success');

        return redirect('tata-usaha/akun-siswa?filter_status=aktif');
    }

    public function editSiswa(Request $request, Kelas $kelas, Siswa $siswa)
    {
        $jenisKelamin = ['laki-laki', 'perempuan'];

        $data = [
            'siswa' => $siswa->where('id_siswa', $request->id)
                ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
                ->join('akun', 'siswa.id_akun', '=', 'akun.id_akun')
                ->first(),
            'kelas' => $kelas
                ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
                ->orderBy('tingkatan')
                ->get(),
            'jenisKelamin' => $jenisKelamin,
        ];

        return view('tata-usaha.edit-siswa', $data);
    }

    public function updateSiswa(Request $request, Siswa $siswa, Role $role, Akun $akun)
    {
        $id_siswa = $request->input('id_siswa');

        $data_akun = $request->validate([
            'username' => 'required',
        ]);

        $data_siswa = $request->validate([
            'nis' => 'required',
            'nama_siswa' => 'required',
            'id_kelas' => 'required',
            'jenis_kelamin' => 'required',
            'nomer_hp' => 'required',
            'angkatan' => 'required',
            'foto_siswa' => 'sometimes',
        ]);

        if (isset($request->password)) {
            $data_akun['password'] = Hash::make($request->password);
        }

        $user = Auth::user();
        $role_akun = $role->where('id_role', $user->id_role)->first('nama_role');
        $data_siswa['pembuat'] = $role_akun->nama_role;

        if ($id_siswa !== null) {
            if ($request->hasFile('foto_siswa') && $request->file('foto_siswa')->isValid()) {
                $foto_file = $request->file('foto_siswa');
                $foto_extension = $foto_file->getClientOriginalExtension();
                $foto_nama = md5($foto_file->getClientOriginalName().time()).'.'.$foto_extension;
                $foto_file->move(public_path('siswa'), $foto_nama);

                $update_data = $siswa->where('id_siswa', $id_siswa)->first();
                $old_file_path = public_path('siswa').'/'.$update_data->foto_siswa;

                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }

                $data_siswa['foto_siswa'] = $foto_nama;
            }

            try {
                $akun->where('id_akun', $siswa->where('id_siswa', $id_siswa)->first()->id_akun)->update($data_akun);
                if ($siswa->where('id_siswa', $id_siswa)->update($data_siswa)) {
                    notify()->success('Data siswa  telah berhasil diupdate', 'Success');
                }

                return redirect('/tata-usaha/akun-siswa')->with('success', 'Data berhasil diupdate');
            } catch (Exception $e) {
                return back()->with('error', 'Data gagal diupdate');
            }
        }

        return back()->with('error', 'Data gagal diupdate');
    }

    public function destroySiswa(Request $request, Role $role, Akun $akun)
    {
        $id_siswa = $request->input('id_siswa');
        $user = Auth::user();
        $role_akun = $role->where('id_role', $user->id_role)->first('nama_role');
        $data['pembuat'] = $role_akun->nama_role;

        $siswa = Siswa::where('id_siswa', $id_siswa)->first();

        if ($siswa) {
            $foto_siswa = $siswa->foto_siswa;
            $siswa->update($data);
            $aksi = $siswa->delete();
            $akun->where('id_akun', $siswa->id_akun)->delete();

            $filePath = public_path('siswa').'/'.$foto_siswa;

            if (file_exists($filePath) && unlink($filePath)) {
                return response()->json(['success' => true]);
            }

            if ($aksi) {
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
        } else {
            $pesan = [
                'success' => false,
                'pesan' => 'Siswa not found',
            ];
        }

        return response()->json($pesan);
    }
}
