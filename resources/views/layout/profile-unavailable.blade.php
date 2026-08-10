@extends('group.layout')
@section('judul', 'Profil belum tersedia')
@section('page-description', 'Akun ini belum memiliki data profil yang dapat ditampilkan.')

@section('isi')
    <section class="operations-empty-state profile-empty-state" aria-labelledby="profile-empty-title">
        <span class="profile-empty-mark"><i class="ph-bold ph-user-circle-dashed" aria-hidden="true"></i></span>
        <strong id="profile-empty-title">Profil belum terhubung</strong>
        <p>{{ $message ?? 'Hubungi Tata Usaha untuk melengkapi hubungan akun dan data sekolah.' }}</p>
        <a href="{{ $backUrl ?? url('/') }}" class="btn btn-primary"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i> Kembali ke dashboard</a>
    </section>
@endsection
