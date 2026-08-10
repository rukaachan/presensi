@extends('layout.layout')
@section('judul', 'Dashboard Guru BK')

@section('isi')
    <section class="dashboard-intro" aria-labelledby="dashboard-title">
        <div>
            <p class="eyebrow">Ringkasan pendampingan siswa</p>
            <h2 id="dashboard-title">Baca kondisi presensi sejak awal.</h2>
            <p>Pantau pola kehadiran dan buka detail ketika seorang siswa membutuhkan tindak lanjut.</p>
        </div>
        <a class="quick-action rounded-md bg-accent text-accent-foreground ring-1 ring-accent" href="{{ route('guru-bk.presensi.index') }}">
            <span>Lihat presensi</span>
            <i class="ph-bold ph-arrow-right" aria-hidden="true"></i>
        </a>
    </section>

    @php
        $metrics = [
            ['label' => 'Hadir', 'value' => $totalHadir, 'meta' => 'Presensi tercatat', 'tone' => 'metric-card--dark'],
            ['label' => 'Izin', 'value' => $totalIzin, 'meta' => 'Ketidakhadiran disetujui', 'tone' => 'metric-card--green'],
            ['label' => 'Alpha', 'value' => $totalAlpha, 'meta' => 'Perlu tindak lanjut', 'tone' => ''],
        ];
    @endphp

    <div class="metric-grid">
        @foreach ($metrics as $metric)
            <article class="metric-card rounded-lg bg-card text-card-foreground ring-1 ring-border {{ $metric['tone'] }}">
                <span class="metric-label">{{ $metric['label'] }}</span>
                <strong class="metric-value">{{ $metric['value'] }}</strong>
                <p class="metric-meta">{{ $metric['meta'] }}</p>
            </article>
        @endforeach
    </div>

    @include('layout.partials.metric-bars', [
        'metrics' => $metrics,
        'visualTitle' => 'Sinyal pendampingan',
        'visualDescription' => 'Gunakan perbandingan ini untuk menentukan prioritas tindak lanjut.',
    ])
@endsection
