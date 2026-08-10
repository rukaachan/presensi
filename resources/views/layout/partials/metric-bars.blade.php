@php
    $visualTitle = $visualTitle ?? 'Gambaran ringkasan';
    $visualDescription = $visualDescription ?? 'Perbandingan jumlah pada kartu di atas, ditampilkan dalam satu skala.';
    $visualId = $visualId ?? 'summary-visual-title';
    $chartMax = 1;

    foreach ($metrics as $metric) {
        $chartMax = max($chartMax, (int) $metric['value']);
    }
@endphp

<section class="dashboard-visualization" aria-labelledby="{{ $visualId }}">
    <header class="visualization-heading">
        <div>
            <h3 id="{{ $visualId }}">{{ $visualTitle }}</h3>
            <p>{{ $visualDescription }}</p>
        </div>
        <span class="visualization-badge">Skala relatif</span>
    </header>

    <div class="visualization-bars" role="list" aria-label="Perbandingan data ringkasan">
        @foreach ($metrics as $metric)
            @php
                $value = (int) $metric['value'];
                $barWidth = round(($value / $chartMax) * 100, 2);
                $barTone = match ($metric['tone']) {
                    'metric-card--dark' => 'visualization-bar-fill--dark',
                    'metric-card--green' => 'visualization-bar-fill--green',
                    default => 'visualization-bar-fill--accent',
                };
            @endphp

            <div class="visualization-bar-row" role="listitem">
                <div class="visualization-bar-meta">
                    <span>{{ $metric['label'] }}</span>
                    <strong>{{ number_format($value) }}</strong>
                </div>
                <div class="visualization-bar-track" aria-hidden="true">
                    <span class="visualization-bar-fill {{ $barTone }}" style="width: {{ $barWidth }}%;"></span>
                </div>
            </div>
        @endforeach
    </div>

    <p class="visualization-caption">Batang terpanjang menunjukkan jumlah terbesar pada ringkasan ini.</p>
</section>
