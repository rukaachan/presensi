@extends('layout.layout')
@section('judul', 'Dashboard Guru Piket')

@section('isi')
    <section class="dashboard-intro" aria-labelledby="dashboard-title">
        <div>
            <p class="eyebrow">Ringkasan piket harian</p>
            <h2 id="dashboard-title">Jaga presensi tetap tepat waktu.</h2>
            <p>Periksa kondisi kehadiran dan validasi pengurus yang bertanggung jawab di setiap kelas.</p>
        </div>
        <a class="quick-action rounded-md bg-accent text-accent-foreground ring-1 ring-accent" href="{{ route('guru-piket.presensi.index') }}">
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
        'visualTitle' => 'Peta kehadiran',
        'visualDescription' => 'Lihat komposisi kehadiran untuk menentukan pemeriksaan berikutnya.',
    ])
@endsection
