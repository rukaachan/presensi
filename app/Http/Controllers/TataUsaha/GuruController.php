<?php

namespace App\Http\Controllers\TataUsaha;

use App\Http\Controllers\Controller;
use App\Models\Akun;
use App\Models\Guru;
use App\Models\GuruBk;
use App\Models\GuruPiket;
use App\Models\Kelas;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GuruController extends Controller
{
    public function showGuru(GuruBk $guru_bk, GuruPiket $guru_piket, Kelas $kelas, Request $request)
    {
        $data = [
            'guruBK' => $guru_bk
                ->join('guru', 'guru_bk.id_guru', '=', 'guru.id_guru')->get(),
            'guruPiket' => $guru_piket
                ->join('guru', 'guru_piket.id_guru', '=', 'guru.id_guru')->get(),
            'kelas' => $kelas
                ->join('guru', 'kelas.id_wali_kelas', '=', 'guru.id_guru')
                ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')->get(),
        ];

        if (! ($request->keyword == null && $request->filter_status == null)) {
            if ($request->filter_status == null) {
                $data = [
                    'guruBK' => $guru_bk
                        ->join('guru', 'guru_bk.id_guru', '=', 'guru.id_guru')->where('nama_guru', 'LIKE', "%$request->keyword%")->get(),
                    'guruPiket' => $guru_piket
                        ->join('guru', 'guru_piket.id_guru', '=', 'guru.id_guru')->where('nama_guru', 'LIKE', "%$request->keyword%")->get(),
                    'kelas' => $kelas
                        ->join('guru', 'kelas.id_wali_kelas', '=', 'guru.id_guru')
                        ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')->where('nama_guru', 'LIKE', "%$request->keyword%")->get(),
                ];
            }
            if ($request->filter_status == '1') {
                $data = [
                    'guruBK' => $guru_bk
                        ->join('guru', 'guru_bk.id_guru', '=', 'guru.id_guru')->where('nama_guru', 'LIKE', "%$request->keyword%")->get(),
                ];
            }
            if ($request->filter_status == '2') {
                $data = [
                    'guruPiket' => $guru_piket
                        ->join('guru', 'guru_piket.id_guru', '=', 'guru.id_guru')->where('nama_guru', 'LIKE', "%$request->keyword%")->get(),
                ];
            }
            if ($request->filter_status == '3') {
                $data = [
                    'kelas' => $kelas
                        ->join('guru', 'kelas.id_wali_kelas', '=', 'guru.id_guru')
                        ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')->where('nama_guru', 'LIKE', "%$request->keyword%")->get(),
                ];
            }
        }

        return view('tata-usaha.guru', $data);
    }

    public function detailGuru(Request $request, Guru $guru, GuruBk $guruBk, GuruPiket $guruPiket, Kelas $kelas)
    {
        $data = [
            'guru' => $guru
                ->join('akun', 'guru.id_akun', '=', 'akun.id_akun')
                ->where('id_guru', $request->id)->first(),
            'guruBk' => $guruBk->where('id_guru', $request->id)->first(),
            'guruPiket' => $guruPiket->where('id_guru', $request->id)->first(),
            'kelas' => $kelas
                ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
                ->where('id_wali_kelas', $request->id)
                ->orderBy('tingkatan')->get(),
        ];

        return view('tata-usaha.detail-guru', $data);
    }

    public function createGuru(Kelas $kelas)
    {
        $data = [
            'kelas' => $kelas
                ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
                ->where('id_wali_kelas', null)->get(),
        ];

        return view('tata-usaha.tambah-guru', $data);
    }

    public function storeGuru(Request $request, Role $role, Guru $guru, GuruPiket $guruPiket, GuruBk $guruBk, Kelas $kelas, Akun $akun)
    {
        $data = $request->validate([
            'nama_guru' => 'required',
            'foto_guru' => 'required',
            'username' => 'required',
            'password' => 'required',
            'status' => 'required',
        ]);

        $user = Auth::user();
        $role_akun = $role->where('id_role', $user->id_role)->first('nama_role');
        $data['pembuat'] = $role_akun->nama_role;

        if ($request->hasFile('foto_guru') && $request->file('foto_guru')->isValid()) {
            $foto_file = $request->file('foto_guru');
            $foto_nama = md5($foto_file->getClientOriginalName().time()).'.'.$foto_file->getClientOriginalExtension();
            $foto_file->move(public_path('guru'), $foto_nama);
            $data['foto_guru'] = $foto_nama;
        } else {
            return back()->with('error', 'File upload failed. Please select a valid file.');
        }

        $status = $request->input('status');
        if ($status == 'Guru BK') {
            $id_akun = $akun->create([
                'id_role' => 5,
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
            ]);
            $sukses = DB::statement('CALL CreateGuruBK(?,?,?,?)', [$id_akun->id_akun, $data['nama_guru'], $foto_nama, $role_akun->nama_role]);
            if ($sukses) {
                notify()->success('Data guru telah berhasil ditambahkan', 'Success');

                return redirect('tata-usaha/akun-guru');
            } else {
                return back()->with('error', 'Data guru gagal ditambahkan');
            }
        }
        if ($status == 'Guru Piket') {
            $id_akun = $akun->create([
                'id_role' => 4,
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
            ]);
            $sukses = DB::statement('CALL CreateGuruPiket(?,?,?,?)', [$id_akun->id_akun, $data['nama_guru'], $foto_nama, $role_akun->nama_role]);
            if ($sukses) {
                notify()->success('Data guru telah berhasil ditambahkan', 'Success');

                return redirect('tata-usaha/akun-guru');
            } else {
                return back()->with('error', 'Data guru gagal ditambahkan');
            }
        } else {
            $id_akun = $akun->create([
                'id_role' => 2,
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
            ]);
            $sukses = DB::statement('CALL CreateWaliKelas(?,?,?,?,?)', [$id_akun->id_akun, $data['nama_guru'], $foto_nama, $role_akun->nama_role, $request->input('status')]);
            if ($sukses) {
                notify()->success('Data guru telah berhasil berhasil ditambahkan', 'Success');

                return redirect('tata-usaha/akun-guru');
            } else {
                return back()->with('error', 'Data guru gagal ditambahkan');
            }
        }
    }

    public function editGuru(Request $request, Kelas $kelas, Guru $guru, GuruBk $guruBk, GuruPiket $guruPiket)
    {
        $guru = [
            'guru' => $guru
                ->join('akun', 'guru.id_akun', '=', 'akun.id_akun')
                ->where('id_guru', $request->id)->first(),
            'guruBk' => $guruBk->where('id_guru', $request->id)->first(),
            'guruPiket' => $guruPiket->where('id_guru', $request->id)->first(),
            'kelas' => $kelas->all(),
        ];

        return view('tata-usaha.edit-guru', $guru);
    }

    public function updateGuru(Request $request, Guru $guru, Role $role, Kelas $kelas, GuruBk $guruBk, GuruPiket $guruPiket, Akun $akun)
    {
        $id_guru = $request->input('id_guru');
        $data = $request->validate([
            'nama_guru' => 'required',
            'foto_guru' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data_akun = $request->validate([
            'username' => 'required',
        ]);

        if (isset($request->password)) {
            $data_akun['password'] = Hash::make($request->password);
        }

        $user = Auth::user();
        $role_akun = $role->where('id_role', $user->id_role)->first('nama_role');
        $data['pembuat'] = $role_akun->nama_role;

        if ($id_guru !== null) {
            $id_akun = $guru->where('id_guru', $id_guru)->first()->id_akun;
            $akun->where('id_akun', $id_akun)->update($data_akun);

            if ($request->hasFile('foto_guru') && $request->file('foto_guru')->isValid()) {
                $foto_file = $request->file('foto_guru');
                $foto_extension = $foto_file->getClientOriginalExtension();
                $foto_nama = md5($foto_file->getClientOriginalName().time()).'.'.$foto_extension;
                $foto_file->move(public_path('guru'), $foto_nama);
                $update_data = $guru->where('id_guru', $id_guru)->first();

                $old_file_path = public_path('guru').'/'.$update_data->foto_guru;
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }

                $data['foto_guru'] = $foto_nama;
            }

            if ($data) {
                $status = $request->input('status');

                if ($kelas->where('id_wali_kelas', $id_guru)->first()) {
                    $kelas->where('id_wali_kelas', $id_guru)->update(['id_wali_kelas' => null]);
                }
                if ($guruPiket->where('id_guru', $id_guru)->first()) {
                    $guruPiket->where('id_guru', $id_guru)->delete();
                }
                if ($guruBk->where('id_guru', $id_guru)->first()) {
                    $guruBk->where('id_guru', $id_guru)->delete();
                }

                $guru->where('id_guru', $id_guru)->update($data);

                if ($status != 'Guru BK' && $status != 'Guru Piket') {
                    $kelas->where('id_kelas', $status)->update(['id_wali_kelas' => $id_guru]);
                    $akun->where('id_akun', $guru->where('id_guru', $id_guru)->first()->id_akun)->update([
                        'id_role' => 2,
                    ]);
                }
                if ($status == 'Guru BK') {
                    $guruBk->create(['id_guru' => $id_guru]);
                    $akun->where('id_akun', $guru->where('id_guru', $id_guru)->first()->id_akun)->update([
                        'id_role' => 5,
                    ]);
                }
                if ($status == 'Guru Piket') {
                    $guruPiket->create(['id_guru' => $id_guru]);
                    $akun->where('id_akun', $guru->where('id_guru', $id_guru)->first()->id_akun)->update([
                        'id_role' => 4,
                    ]);
                }
                notify()->success('Data guru telah berhasil diupdate', 'Success');

                return redirect('tata-usaha/akun-guru');
            }
        }

        return back()->with('error', 'Data gagal diupdate');
    }

    public function destroyGuru(Request $request, Role $role, Kelas $kelas, GuruPiket $guruPiket, GuruBk $guruBk, Akun $akun)
    {
        $id_guru = $request->input('id_guru');
        $user = Auth::user();
        $role_akun = $role->where('id_role', $user->id_role)->first('nama_role');
        $data['pembuat'] = $role_akun->nama_role;

        $guru = Guru::where('id_guru', $id_guru)->first();

        if ($guru) {
            if ($kelas->where('id_wali_kelas', $id_guru)->first()) {
                $kelas->where('id_wali_kelas', $id_guru)->update(['id_wali_kelas' => null]);
            }
            if ($guruPiket->where('id_guru', $id_guru)->first()) {
                $guruPiket->where('id_guru', $id_guru)->delete();
            }
            if ($guruBk->where('id_guru', $id_guru)->first()) {
                $guruBk->where('id_guru', $id_guru)->delete();
            }

            $pembuat = $guru->update($data);
            $hapus_guru = $guru->delete();
            $akun->where('id_akun', $guru->id_akun)->delete();

            $filePath = public_path('guru').'/'.$guru->foto_guru;

            if (file_exists($filePath) && unlink($filePath)) {
                return response()->json(['success' => true]);
            }

            if ($pembuat || $hapus_guru) {
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
                'pesan' => 'Guru tidak ditemukan',
            ];
        }

        return response()->json($pesan);
    }
}
