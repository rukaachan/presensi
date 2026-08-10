@extends('layout.layout')
@section('judul', 'Presensi kelas')
@section('page-description', 'Tinjau dan perbarui catatan kehadiran siswa dalam kelas Anda.')

@section('isi')
    <section class="workspace-page" aria-labelledby="wali-presensi-title">
        <header class="workspace-intro"><div><p class="eyebrow">Ruang wali kelas</p><h2 id="wali-presensi-title">Catatan kehadiran kelas.</h2><p>Filter tanggal atau status untuk menindaklanjuti catatan yang penting.</p></div><span class="workspace-index">03 / Kelas</span></header>
        <form class="workspace-filters attendance-filters" action="" method="get" id="form"><label class="search-field" for="keyword"><span class="sr-only">Cari presensi</span><i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i><input id="keyword" type="search" name="keyword" value="{{ old('keyword', request('keyword')) }}" placeholder="Cari nama siswa..."></label><input type="date" class="form-control filter" id="tanggal" value="{{ old('filter_tanggal', request('filter_tanggal')) }}" name="filter_tanggal" aria-label="Filter tanggal"><select class="form-select filter" name="filter_kehadiran"><option value="">Semua status</option><option value="hadir" {{ request('filter_kehadiran') === 'hadir' ? 'selected' : '' }}>Hadir</option><option value="izin" {{ request('filter_kehadiran') === 'izin' ? 'selected' : '' }}>Izin</option><option value="alpha" {{ request('filter_kehadiran') === 'alpha' ? 'selected' : '' }}>Alpha</option></select><button type="submit" class="btn btn-secondary">Terapkan</button><button type="button" class="btn btn-primary" id="downloadPDF"><i class="ph-bold ph-download-simple" aria-hidden="true"></i> Unduh PDF</button></form>
        <div class="table-scroll" tabindex="0" aria-label="Tabel presensi kelas"><table class="table table-bordered"><thead><tr><th scope="col">No</th><th scope="col">NIS</th><th scope="col">Nama siswa</th><th scope="col">Tanggal</th><th scope="col">Kelas</th><th scope="col">Kehadiran</th><th scope="col">Bukti</th><th scope="col">Keterangan</th><th scope="col">Aksi</th></tr></thead><tbody>@forelse ($presensi as $item)<tr><td>{{ ($presensi->currentPage() - 1) * $presensi->perPage() + $loop->iteration }}</td><td>{{ $item->nis }}</td><td><strong class="table-primary-text">{{ $item->nama_siswa }}</strong></td><td>{{ $item->tanggal ? \Illuminate\Support\Carbon::parse($item->tanggal)->locale('id')->isoFormat('D MMM YYYY') : '-' }}</td><td>{{ $item->tingkatan.' '.$item->nama_jurusan.' '.$item->nama_kelas }}</td><td><span class="status-badge status-badge--{{ $item->status_kehadiran }}">{{ ucfirst($item->status_kehadiran) }}</span></td><td>@if ($item->foto_bukti && file_exists(public_path('presensi_bukti/'.$item->foto_bukti)))<img src="{{ asset('presensi_bukti/'.$item->foto_bukti) }}" alt="Bukti presensi {{ $item->nama_siswa }}">@else<span class="evidence-placeholder"><i class="ph-bold ph-image-square" aria-hidden="true"></i> Tidak ada</span>@endif</td><td>{{ $item->keterangan ?: '—' }}</td><td class="table-actions"><a class="icon-action icon-action--edit" href="{{ route('wali-kelas.presensi-siswa.edit', ['id' => $item->id_presensi]) }}" aria-label="Edit presensi {{ $item->nama_siswa }}" title="Edit presensi"><i class="ph-bold ph-pencil-simple" aria-hidden="true"></i></a></td></tr>@empty<tr><td colspan="9"><div class="table-empty-state"><i class="ph-bold ph-calendar-check" aria-hidden="true"></i><strong>Belum ada presensi yang cocok</strong><span>Ubah filter untuk melihat catatan.</span></div></td></tr>@endforelse</tbody></table></div>{{ $presensi->links() }}
    </section>
@endsection
@section('footer')
    <script type="module">
        document.getElementById('downloadPDF')?.addEventListener('click', () => {
            const form = document.getElementById('form');
            if (!form) return;
            form.action = '{{ route('wali-kelas.presensi-siswa.pdf') }}';
            form.submit();
        });
    </script>
@endsection
