<?php

namespace App\Services;

use App\Models\PresensiSiswa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PresensiFilterService
{
    public function buildBaseQuery(PresensiSiswa $presensi): Builder
    {
        $query = $presensi
            ->join('siswa', 'siswa.id_siswa', '=', 'presensi_siswa.id_siswa')
            ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
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
            );

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

        return $query;
    }

    public function filter(Request $request, Builder $query, bool $usePagination = true, array $options = [])
    {
        $keywords = trim((string) $request->keyword);
        $searchColumns = $options['search_columns'] ?? [
            'nama_siswa',
            'tanggal',
            'status_kehadiran',
            'tingkatan',
            'nama_jurusan',
            'nama_kelas',
        ];

        if (! empty($keywords) && ! empty($searchColumns)) {
            $query->where(function ($builder) use ($keywords, $searchColumns) {
                foreach ($searchColumns as $index => $column) {
                    if ($index === 0) {
                        $builder->where($column, 'LIKE', "%$keywords%");

                        continue;
                    }

                    $builder->orWhere($column, 'LIKE', "%$keywords%");
                }
            });
        }

        $monthFilter = $options['month_filter'] ?? ['request_key' => 'filter_bulan', 'column' => 'tanggal'];
        $monthRequestKey = (string) $monthFilter['request_key'];
        $monthValue = $request->input($monthRequestKey);
        if ($request->filled($monthRequestKey)) {
            $query->whereMonth($monthFilter['column'], (string) $monthValue);
        }

        $dateFilter = $options['date_filter'] ?? ['request_key' => 'filter_tanggal', 'column' => 'presensi_siswa.tanggal'];
        $dateRequestKey = (string) $dateFilter['request_key'];
        $dateValue = $request->input($dateRequestKey);
        if ($request->filled($dateRequestKey)) {
            $query->whereDate($dateFilter['column'], $dateValue);
        }

        $kehadiranFilter = $options['kehadiran_filter'] ?? ['request_key' => 'filter_kehadiran', 'column' => 'presensi_siswa.status_kehadiran'];
        $kehadiranRequestKey = (string) $kehadiranFilter['request_key'];
        $kehadiranValue = $request->input($kehadiranRequestKey);
        if ($request->filled($kehadiranRequestKey)) {
            $query->where($kehadiranFilter['column'], $kehadiranValue);
        }

        $kelasFilter = $options['kelas_filter'] ?? ['request_key' => 'filter_kelas', 'column' => 'kelas.id_kelas'];
        $kelasRequestKey = (string) $kelasFilter['request_key'];
        $kelasValue = $request->input($kelasRequestKey);
        if ($request->filled($kelasRequestKey)) {
            $query->where($kelasFilter['column'], $kelasValue);
        }

        $query->orderBy($options['order_column'] ?? 'presensi_siswa.id_presensi', $options['order_direction'] ?? 'asc');

        if ($usePagination) {
            return $query->simplePaginate($options['per_page'] ?? 25)->appends($request->query());
        }

        return $query->get();
    }
}
