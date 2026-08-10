@extends('layout.layout')
@section('judul', 'Dashboard Wali Kelas')

@section('isi')
    <section class="dashboard-intro" aria-labelledby="dashboard-title">
        <div>
            <p class="eyebrow">Ringkasan kelas</p>
            <h2 id="dashboard-title">Pahami kondisi kelas setiap hari.</h2>
            <p>Tinjau presensi hari ini, kelola pengurus kelas, dan jaga data siswa tetap terbaru.</p>
        </div>
        <a class="quick-action rounded-md bg-accent text-accent-foreground ring-1 ring-accent" href="{{ route('wali-kelas.presensi-siswa.index') }}">
            <span>Tinjau presensi</span>
            <i class="ph-bold ph-arrow-right" aria-hidden="true"></i>
        </a>
    </section>

    @php
        $metrics = [
            ['label' => 'Siswa', 'value' => $totalSiswa, 'meta' => 'Di kelas Anda', 'tone' => 'metric-card--dark'],
            ['label' => 'Hadir', 'value' => $totalHadir, 'meta' => 'Presensi tercatat', 'tone' => 'metric-card--green'],
            ['label' => 'Izin', 'value' => $totalIzin, 'meta' => 'Ketidakhadiran disetujui', 'tone' => ''],
            ['label' => 'Alpha', 'value' => $totalAlpha, 'meta' => 'Perlu tindak lanjut', 'tone' => ''],
        ];
    @endphp

    <div class="metric-grid metric-grid--four">
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
        'visualTitle' => 'Keseimbangan kelas',
        'visualDescription' => 'Bandingkan siswa hadir, izin, dan alpha dalam ruang kelas Anda.',
    ])
@endsection
