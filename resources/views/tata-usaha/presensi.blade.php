@extends('layout.layout')
@section('judul', 'Kelola presensi')
@section('page-description', 'Telusuri catatan kehadiran sekolah dan unduh hasil yang sedang difilter.')

@section('isi')
    <section class="workspace-page" aria-labelledby="presensi-workspace-title">
        <header class="workspace-intro"><div><p class="eyebrow">Catatan kehadiran</p><h2 id="presensi-workspace-title">Semua presensi, satu ruang.</h2><p>Gunakan satu tanggal atau bulan pada satu waktu untuk hasil yang lebih terarah.</p></div><span class="workspace-index">06 / Operasi</span></header>
        <form class="workspace-filters attendance-filters" action="" method="get" id="form"><label class="search-field" for="keyword"><span class="sr-only">Cari presensi</span><i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i><input id="keyword" type="search" name="keyword" value="{{ old('keyword', request('keyword')) }}" placeholder="Cari nama siswa..."></label><select class="form-select filter" name="filter_kelas"><option value="">Semua kelas</option>@foreach ($kelas as $item)<option value="{{ $item->id_kelas }}" {{ request('filter_kelas') == $item->id_kelas ? 'selected' : '' }}>{{ $item->tingkatan.' '.$item->nama_jurusan.' '.$item->nama_kelas }}</option>@endforeach</select><select class="form-select" id="filter_bulan" name="filter_bulan"><option value="">Semua bulan</option>@foreach (['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'] as $value => $label)<option value="{{ $value }}" {{ request('filter_bulan') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select><input type="date" class="form-control" id="filter_tanggal" value="{{ old('filter_tanggal', request('filter_tanggal')) }}" name="filter_tanggal" aria-label="Filter tanggal"><select class="form-select filter" name="filter_kehadiran"><option value="">Semua status</option><option value="hadir" {{ request('filter_kehadiran') === 'hadir' ? 'selected' : '' }}>Hadir</option><option value="izin" {{ request('filter_kehadiran') === 'izin' ? 'selected' : '' }}>Izin</option><option value="alpha" {{ request('filter_kehadiran') === 'alpha' ? 'selected' : '' }}>Alpha</option></select><button type="submit" class="btn btn-secondary">Terapkan</button><button type="button" class="btn btn-primary" id="downloadPDF"><i class="ph-bold ph-download-simple" aria-hidden="true"></i> Unduh PDF</button></form>
        <div class="table-scroll" tabindex="0" aria-label="Tabel presensi"><table class="table table-bordered"><thead><tr><th scope="col">No</th><th scope="col">NIS</th><th scope="col">Nama siswa</th><th scope="col">Tanggal</th><th scope="col">Kelas</th><th scope="col">Kehadiran</th><th scope="col">Bukti</th><th scope="col">Keterangan</th></tr></thead><tbody>
            @forelse ($presensi as $item)<tr><td>{{ ($presensi->currentPage() - 1) * $presensi->perPage() + $loop->iteration }}</td><td>{{ $item->nis }}</td><td><strong class="table-primary-text">{{ $item->nama_siswa }}</strong></td><td>{{ $item->tanggal ? \Illuminate\Support\Carbon::parse($item->tanggal)->locale('id')->isoFormat('D MMM YYYY') : '-' }}</td><td>{{ $item->tingkatan.' '.$item->nama_jurusan.' '.$item->nama_kelas }}</td><td><span class="status-badge status-badge--{{ $item->status_kehadiran }}">{{ ucfirst($item->status_kehadiran) }}</span></td><td>@include('layout.partials.attendance-evidence', ['record' => $item, 'alt' => 'Bukti presensi '.$item->nama_siswa])</td><td>{{ $item->keterangan ?: '—' }}</td></tr>@empty<tr><td colspan="8"><div class="table-empty-state"><i class="ph-bold ph-calendar-check" aria-hidden="true"></i><strong>Belum ada presensi yang cocok</strong><span>Persempit atau ubah filter untuk melihat catatan.</span></div></td></tr>@endforelse
        </tbody></table></div>{{ $presensi->links() }}
    </section>
@endsection
@section('footer')
    <script type="module">
        document.getElementById('downloadPDF')?.addEventListener('click', () => {
            const form = document.getElementById('form');
            if (!form) return;
            form.action = '{{ route('tata-usaha.presensi.pdf') }}';
            form.submit();
        });
    </script>
@endsection
