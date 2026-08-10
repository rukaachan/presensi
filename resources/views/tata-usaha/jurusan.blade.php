@extends('layout.layout')
@section('judul', 'Kelola jurusan')
@section('page-description', 'Atur daftar program keahlian yang digunakan oleh kelas aktif.')
@section('page-actions')
    <a href="{{ route('tata-usaha.jurusan.create') }}" class="btn btn-primary page-action">
        <i class="ph-bold ph-plus" aria-hidden="true"></i>
        Tambah jurusan
    </a>
@endsection

@section('isi')
    <section class="workspace-page" aria-labelledby="jurusan-workspace-title">
        <header class="workspace-intro">
            <div>
                <p class="eyebrow">Data referensi</p>
                <h2 id="jurusan-workspace-title">Program keahlian.</h2>
                <p>{{ $jurusan->count() }} program tersedia untuk dipakai pada data kelas.</p>
            </div>
            <span class="workspace-index">01 / Referensi</span>
        </header>

        <form action="" method="get" class="workspace-filters" id="form">
            <label class="search-field" for="keyword">
                <span class="sr-only">Cari jurusan</span>
                <i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i>
                <input id="keyword" type="search" name="keyword" value="{{ old('keyword', request('keyword')) }}" placeholder="Cari nama jurusan...">
            </label>
            <button type="submit" class="btn btn-secondary">Cari</button>
        </form>

        <div class="table-scroll" tabindex="0" aria-label="Tabel jurusan">
            <table class="table table-bordered">
                <thead>
                    <tr><th scope="col">No</th><th scope="col">Nama jurusan</th><th scope="col">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($jurusan as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong class="table-primary-text">{{ $item->nama_jurusan }}</strong></td>
                            <td class="table-actions">
                                <a class="icon-action icon-action--edit" href="{{ route('tata-usaha.jurusan.edit', ['id' => $item->id_jurusan]) }}" aria-label="Edit {{ $item->nama_jurusan }}" title="Edit jurusan"><i class="ph-bold ph-pencil-simple" aria-hidden="true"></i></a>
                                <button type="button" class="icon-action icon-action--danger" data-delete-url="{{ route('tata-usaha.jurusan.destroy') }}" data-delete-field="id_jurusan" data-delete-id="{{ $item->id_jurusan }}" aria-label="Hapus {{ $item->nama_jurusan }}" title="Hapus jurusan"><i class="ph-bold ph-trash" aria-hidden="true"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><div class="table-empty-state"><i class="ph-bold ph-buildings" aria-hidden="true"></i><strong>Belum ada jurusan</strong><span>Tambahkan program keahlian untuk mulai mengatur kelas.</span></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
