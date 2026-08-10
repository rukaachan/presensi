@extends('layout.layout')
@section('judul', 'Log kelas')
@section('page-description', 'Tinjau perubahan yang berkaitan dengan data kelas Anda.')

@section('isi')
    <section class="workspace-page" aria-labelledby="wali-logs-title">
        <header class="workspace-intro"><div><p class="eyebrow">Jejak kelas</p><h2 id="wali-logs-title">Perubahan yang tercatat.</h2><p>Log membantu Anda memahami perubahan data siswa dan presensi.</p></div><span class="workspace-index">04 / Kelas</span></header>
        <div class="table-scroll" tabindex="0" aria-label="Tabel log kelas"><table class="table table-bordered"><thead><tr><th scope="col">No</th><th scope="col">Tabel</th><th scope="col">Aktor</th><th scope="col">Tanggal</th><th scope="col">Jam</th><th scope="col">Aksi</th><th scope="col">Record</th></tr></thead><tbody>@php $counter = 1; @endphp @forelse ($logs as $item) @if ($item->tabel !== 'guru' && $item->aktor !== 'Tata Usaha')<tr><td>{{ $counter++ }}</td><td>{{ $item->tabel }}</td><td><strong class="table-primary-text">{{ $item->aktor }}</strong></td><td>{{ $item->tanggal }}</td><td class="mono-data">{{ $item->jam }}</td><td><span class="log-action log-action--{{ strtolower($item->aksi) }}">{{ $item->aksi }}</span></td><td>{{ $item->record }}</td></tr>@endif @empty<tr><td colspan="7"><div class="table-empty-state"><i class="ph-bold ph-notebook" aria-hidden="true"></i><strong>Belum ada log kelas</strong><span>Aktivitas kelas akan muncul di sini.</span></div></td></tr>@endforelse</tbody></table></div>
    </section>
@endsection
