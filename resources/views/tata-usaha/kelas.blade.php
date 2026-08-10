@extends('layout.layout')
@section('judul', 'Kelola kelas')
@section('page-description', 'Kelola kelas aktif, tingkat, program keahlian, dan kesiapan wali kelas.')
@section('page-actions')
    <a href="{{ route('tata-usaha.kelas.create') }}" class="btn btn-primary page-action"><i class="ph-bold ph-plus" aria-hidden="true"></i> Tambah kelas</a>
@endsection

@section('isi')
    <section class="workspace-page" aria-labelledby="kelas-workspace-title">
        <header class="workspace-intro">
            <div><p class="eyebrow">Data akademik</p><h2 id="kelas-workspace-title">Struktur kelas.</h2><p>Filter kelas yang perlu diperbarui tanpa kehilangan konteksnya.</p></div>
            <span class="workspace-index">02 / Referensi</span>
        </header>

        <form action="" method="get" class="workspace-filters" id="form">
            <label class="search-field" for="keyword"><span class="sr-only">Cari kelas</span><i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i><input id="keyword" type="search" name="keyword" value="{{ old('keyword', request('keyword')) }}" placeholder="Cari nama kelas..."></label>
            <select class="form-select filter" name="filter_tingkatan"><option value="">Semua tingkatan</option><option value="X" {{ request('filter_tingkatan') === 'X' ? 'selected' : '' }}>X</option><option value="XI" {{ request('filter_tingkatan') === 'XI' ? 'selected' : '' }}>XI</option><option value="XII" {{ request('filter_tingkatan') === 'XII' ? 'selected' : '' }}>XII</option></select>
            <select class="form-select filter" name="filter_jurusan"><option value="">Semua jurusan</option>@foreach ($jurusan as $item)<option value="{{ $item->id_jurusan }}" {{ request('filter_jurusan') == $item->id_jurusan ? 'selected' : '' }}>{{ $item->nama_jurusan }}</option>@endforeach</select>
            <select class="form-select filter" name="filter_status"><option value="">Semua status</option><option value="aktif" {{ request('filter_status') === 'aktif' ? 'selected' : '' }}>Aktif</option><option value="tidak_aktif" {{ request('filter_status') === 'tidak_aktif' ? 'selected' : '' }}>Tidak aktif</option></select>
            <button type="submit" class="btn btn-secondary">Terapkan</button>
        </form>

        <div class="table-scroll" tabindex="0" aria-label="Tabel kelas">
            <table class="table table-bordered">
                <thead><tr><th scope="col">No</th><th scope="col">Tingkatan</th><th scope="col">Jurusan</th><th scope="col">Nama kelas</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($kelas as $item)
                        <tr><td>{{ $loop->iteration }}</td><td>{{ $item->tingkatan }}</td><td>{{ $item->nama_jurusan }}</td><td><strong class="table-primary-text">{{ $item->nama_kelas }}</strong></td><td><span class="status-badge status-badge--{{ $item->status_kelas === 'aktif' ? 'hadir' : 'alpha' }}">{{ str_replace('_', ' ', $item->status_kelas) }}</span></td><td class="table-actions"><a class="icon-action icon-action--info" href="{{ route('tata-usaha.kelas.detail', ['id' => $item->id_kelas]) }}" aria-label="Lihat {{ $item->nama_kelas }}" title="Lihat kelas"><i class="ph-bold ph-arrow-up-right" aria-hidden="true"></i></a><a class="icon-action icon-action--edit" href="{{ route('tata-usaha.kelas.edit', ['id' => $item->id_kelas]) }}" aria-label="Edit {{ $item->nama_kelas }}" title="Edit kelas"><i class="ph-bold ph-pencil-simple" aria-hidden="true"></i></a><button type="button" class="icon-action icon-action--danger" data-delete-url="{{ route('tata-usaha.kelas.destroy') }}" data-delete-field="id_kelas" data-delete-id="{{ $item->id_kelas }}" aria-label="Hapus {{ $item->nama_kelas }}" title="Hapus kelas"><i class="ph-bold ph-trash" aria-hidden="true"></i></button></td></tr>
                    @empty
                        <tr><td colspan="6"><div class="table-empty-state"><i class="ph-bold ph-chalkboard" aria-hidden="true"></i><strong>Belum ada kelas yang cocok</strong><span>Ubah filter atau tambahkan kelas baru.</span></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @php $classSummary = [['label' => 'Hasil ditampilkan', 'value' => $kelas->count()], ['label' => 'Kelas aktif', 'value' => $kelas->where('status_kelas', 'aktif')->count()], ['label' => 'Tingkatan', 'value' => $kelas->pluck('tingkatan')->unique()->count()], ['label' => 'Jurusan terwakili', 'value' => $kelas->pluck('id_jurusan')->unique()->count()]]; @endphp
        <section class="workspace-summary" aria-labelledby="class-summary-title"><div class="workspace-summary-heading"><div><h3 id="class-summary-title">Ringkasan hasil</h3><p>Ikhtisar dari kelas yang sesuai dengan filter saat ini.</p></div><i class="ph-bold ph-chart-bar" aria-hidden="true"></i></div><div class="workspace-summary-grid">@foreach ($classSummary as $summary)<div class="workspace-summary-item"><span>{{ $summary['label'] }}</span><strong>{{ $summary['value'] }}</strong></div>@endforeach</div></section>
    </section>
@endsection
