@extends('layout.layout')
@section('judul', 'Dashboard Pengurus Kelas')

@section('isi')
    <section class="dashboard-intro" aria-labelledby="dashboard-title">
        <div>
            <p class="eyebrow">Ringkasan operasional kelas</p>
            <h2 id="dashboard-title">Jaga catatan kelas tetap lengkap.</h2>
            <p>Catat presensi, validasi setiap waktu istirahat, dan siapkan histori untuk wali kelas.</p>
        </div>
        <a class="quick-action rounded-md bg-accent text-accent-foreground ring-1 ring-accent" href="{{ route('pengurus-kelas.presensi.index') }}">
            <span>Catat presensi</span>
            <i class="ph-bold ph-arrow-right" aria-hidden="true"></i>
        </a>
    </section>

    @php
        $metrics = [
            ['label' => 'Hadir', 'value' => $totalHadir, 'meta' => 'Presensi tercatat', 'tone' => 'metric-card--dark'],
            ['label' => 'Izin', 'value' => $totalIzin, 'meta' => 'Ketidakhadiran disetujui', 'tone' => 'metric-card--green'],
            ['label' => 'Alpha', 'value' => $totalAlpha, 'meta' => 'Perlu validasi', 'tone' => ''],
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
        'visualTitle' => 'Status validasi',
        'visualDescription' => 'Bandingkan catatan kehadiran yang sudah masuk sebelum divalidasi.',
    ])
@endsection
