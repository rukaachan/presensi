<?php

namespace App\Http\Controllers\TataUsaha;

use App\Http\Controllers\Controller;
use App\Models\Logs;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function logs(Logs $logs, Request $request)
    {
        $filter = $logs->orderBy('id_log', 'desc')
            ->where(function ($query) use ($request) {
                $query->where('tabel', 'LIKE', "%$request->keyword%")
                    ->orWhere('aktor', 'LIKE', "%$request->keyword%")
                    ->orWhere('tanggal', 'LIKE', "%$request->keyword%")
                    ->orWhere('jam', 'LIKE', "%$request->keyword%")
                    ->orWhere('aksi', 'LIKE', "%$request->keyword%");
            })
            ->where('status', 'aktif');

        if ($request->filter_tabel != null) {
            $filter = $filter->where('tabel', $request->filter_tabel);
        }

        if ($request->filter_aktor != null) {
            $filter = $filter->where('aktor', $request->filter_aktor);
        }

        if ($request->filter_tanggal != null) {
            $filter = $filter->where('tanggal', $request->filter_tanggal);
        }

        if ($request->filter_aksi != null) {
            $filter = $filter->where('aksi', $request->filter_aksi);
        }

        $data = [
            'logs' => $filter->simplePaginate(25)->withQueryString(),
        ];

        return view('tata-usaha.logs', $data);
    }

    public function deleteLogs(Logs $logs, Request $request)
    {
        if ($request->input('id_logs') != null) {
            foreach ($request->id_logs as $p) {
                $logs::where('id_log', $p)->update(['status' => 'tidak_aktif']);
            }
            notify()->success('Data logs telah berhasil dihapus', 'Success');
        }

        return redirect('/tata-usaha/logs')->with('success', 'Data logs berhasil dihapus');
    }
}
