<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Laporan presensi' }}</title>
    <style>
        @page { margin: 28px 30px; }
        * { box-sizing: border-box; }
        body { color: #24211f; font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; margin: 0; }
        .report-header { border-bottom: 2px solid #e85b2a; margin-bottom: 18px; padding-bottom: 12px; }
        .report-kicker { color: #77716c; font-size: 8px; letter-spacing: 1.4px; margin: 0 0 5px; text-transform: uppercase; }
        h1 { font-size: 20px; letter-spacing: -0.5px; margin: 0; }
        .report-date { color: #77716c; font-size: 9px; margin: 5px 0 0; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #292725; color: #fff; font-size: 8px; letter-spacing: 0.5px; padding: 8px 7px; text-align: left; text-transform: uppercase; }
        td { border-bottom: 1px solid #ddd8d3; padding: 7px; vertical-align: top; }
        tr:nth-child(even) td { background: #faf8f6; }
        .number { color: #77716c; text-align: right; width: 28px; }
        .status { font-weight: bold; text-transform: capitalize; }
        .footnote { color: #77716c; font-size: 8px; margin-top: 12px; }
    </style>
</head>
<body>
    @php $records = collect($records ?? ($presensi ?? ($kelas ?? []))); @endphp
    <header class="report-header">
        <p class="report-kicker">SmartPresensi · laporan operasional</p>
        <h1>{{ $title ?? 'Laporan presensi' }}</h1>
        <p class="report-date">Dibuat {{ now('Asia/Jakarta')->locale('id')->isoFormat('D MMMM YYYY, HH:mm') }}</p>
    </header>
    <table>
        <thead>
            <tr>
                <th class="number">No</th>
                <th>NIS</th>
                <th>Nama siswa</th>
                @if ($records->first() && isset($records->first()->tingkatan))<th>Kelas</th>@endif
                <th>Tanggal</th>
                <th>Kehadiran</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                <tr>
                    <td class="number">{{ $loop->iteration }}</td>
                    <td>{{ $record->nis }}</td>
                    <td>{{ $record->nama_siswa }}</td>
                    @if (isset($record->tingkatan))<td>{{ $record->tingkatan.' '.$record->nama_jurusan.' '.$record->nama_kelas }}</td>@endif
                    <td>{{ $record->tanggal }}</td>
                    <td class="status">{{ $record->status_kehadiran }}</td>
                    <td>{{ $record->keterangan ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Tidak ada catatan pada filter yang dipilih.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="footnote">Dokumen ini dibuat dari catatan yang tersimpan di SmartPresensi.</p>
</body>
</html>
