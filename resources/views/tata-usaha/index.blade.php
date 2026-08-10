@extends('layout.layout')
@section('judul', 'Dashboard Tata Usaha')

@section('isi')
    <section class="dashboard-intro operations-intro" aria-labelledby="dashboard-title">
        <div>
            <p class="eyebrow">Operasi presensi hari ini</p>
            <h2 id="dashboard-title">Kondisi sekolah, langsung terlihat.</h2>
            <p>{{ $operationalDate->locale('id')->isoFormat('dddd, D MMMM YYYY') }} · Pantau kelengkapan kelas dan tindak lanjuti pengecualian.</p>
        </div>
        <a class="quick-action rounded-md bg-accent text-accent-foreground ring-1 ring-accent" href="{{ route('tata-usaha.presensi.index') }}">
            <span>Tinjau presensi</span>
            <i class="ph-bold ph-arrow-right" aria-hidden="true"></i>
        </a>
    </section>

    <section class="daily-kpi-grid" aria-label="Ringkasan operasi hari ini">
        <article class="daily-kpi daily-kpi--primary">
            <div class="daily-kpi-heading">
                <span>Tercatat</span>
                <i class="ph-bold ph-check-circle" aria-hidden="true"></i>
            </div>
            <strong>{{ $dailySummary['totalRecorded'] }} <small>/ {{ $dailySummary['totalActiveStudents'] }}</small></strong>
            <div class="daily-kpi-progress" aria-hidden="true">
                <span style="width: {{ $dailySummary['completionRate'] }}%;"></span>
            </div>
            <p>{{ $dailySummary['completionRate'] }}% siswa aktif telah memiliki catatan.</p>
        </article>

        <article class="daily-kpi">
            <div class="daily-kpi-heading">
                <span>Belum tercatat</span>
                <i class="ph-bold ph-clock-countdown" aria-hidden="true"></i>
            </div>
            <strong>{{ $dailySummary['totalMissing'] }}</strong>
            <p>Siswa aktif belum memiliki presensi hari ini.</p>
        </article>

        <article class="daily-kpi {{ $dailySummary['needsReview'] > 0 ? 'daily-kpi--attention' : '' }}">
            <div class="daily-kpi-heading">
                <span>Perlu ditinjau</span>
                <i class="ph-bold ph-warning-circle" aria-hidden="true"></i>
            </div>
            <strong>{{ $dailySummary['needsReview'] }}</strong>
            <p>Gabungan catatan alpha dan validasi tertunda.</p>
        </article>

        <article class="daily-kpi">
            <div class="daily-kpi-heading">
                <span>Kelas lengkap</span>
                <i class="ph-bold ph-chalkboard" aria-hidden="true"></i>
            </div>
            <strong>{{ $dailySummary['classesComplete'] }} <small>/ {{ $dailySummary['totalActiveClasses'] }}</small></strong>
            <p>Kelas dengan presensi seluruh siswa aktif.</p>
        </article>
    </section>

    <div class="operations-grid">
        <section class="operations-panel" aria-labelledby="class-readiness-title">
            <header class="operations-panel-heading">
                <div>
                    <h3 id="class-readiness-title">Kesiapan kelas</h3>
                    <p>Progres presensi siswa aktif per kelas.</p>
                </div>
                <a href="{{ route('tata-usaha.presensi.index') }}">Lihat semua <i class="ph-bold ph-arrow-up-right" aria-hidden="true"></i></a>
            </header>

            @forelse ($classReadiness as $class)
                <a class="class-readiness-row" href="{{ route('tata-usaha.presensi.index', ['filter_kelas' => $class->id_kelas]) }}">
                    <div class="class-readiness-copy">
                        <strong>{{ $class->tingkatan }} {{ $class->nama_jurusan }} {{ $class->nama_kelas }}</strong>
                        <span>{{ $class->totalRecorded }} dari {{ $class->totalStudents }} siswa tercatat</span>
                    </div>
                    <div class="class-readiness-progress" aria-hidden="true">
                        <span style="width: {{ $class->completionRate }}%;"></span>
                    </div>
                    <span class="class-readiness-status {{ $class->isComplete ? 'is-complete' : '' }}">
                        {{ $class->isComplete ? 'Lengkap' : $class->completionRate . '%' }}
                    </span>
                </a>
            @empty
                <div class="operations-empty-state">
                    <i class="ph-bold ph-chalkboard" aria-hidden="true"></i>
                    <strong>Belum ada kelas aktif</strong>
                    <p>Aktifkan kelas untuk mulai memantau kesiapan presensi.</p>
                </div>
            @endforelse
        </section>

        <aside class="attention-panel" aria-labelledby="attention-title">
            <header class="operations-panel-heading">
                <div>
                    <h3 id="attention-title">Perlu perhatian</h3>
                    <p>Prioritas tindak lanjut hari ini.</p>
                </div>
            </header>

            <a class="attention-item" href="{{ route('tata-usaha.presensi.index', ['filter_kehadiran' => 'alpha']) }}">
                <span class="attention-item-icon attention-item-icon--danger"><i class="ph-bold ph-user-minus" aria-hidden="true"></i></span>
                <span><strong>{{ $dailySummary['totalAbsent'] }} Alpha</strong><small>Periksa siswa tanpa keterangan.</small></span>
                <i class="ph-bold ph-caret-right" aria-hidden="true"></i>
            </a>
            <a class="attention-item" href="{{ route('tata-usaha.presensi.index') }}">
                <span class="attention-item-icon"><i class="ph-bold ph-seal-question" aria-hidden="true"></i></span>
                <span><strong>{{ $dailySummary['pendingValidation'] }} Belum divalidasi</strong><small>Catatan menunggu keputusan pengurus.</small></span>
                <i class="ph-bold ph-caret-right" aria-hidden="true"></i>
            </a>
            <a class="attention-item" href="{{ route('tata-usaha.kelas.index', ['filter_status' => 'aktif']) }}">
                <span class="attention-item-icon"><i class="ph-bold ph-list-checks" aria-hidden="true"></i></span>
                <span><strong>{{ max(0, $dailySummary['totalActiveClasses'] - $dailySummary['classesComplete']) }} Kelas belum lengkap</strong><small>Pantau kelas yang masih mengumpulkan presensi.</small></span>
                <i class="ph-bold ph-caret-right" aria-hidden="true"></i>
            </a>
        </aside>
    </div>

    <section class="activity-panel" aria-labelledby="recent-activity-title">
        <header class="operations-panel-heading">
            <div>
                <h3 id="recent-activity-title">Aktivitas terbaru</h3>
                <p>Perubahan terakhir pada ruang kerja sekolah.</p>
            </div>
            <a href="{{ route('tata-usaha.logs.index') }}">Buka logs <i class="ph-bold ph-arrow-up-right" aria-hidden="true"></i></a>
        </header>

        <div class="activity-list">
            @forelse ($recentLogs as $log)
                <div class="activity-item">
                    <span class="activity-item-mark"><i class="ph-bold ph-activity" aria-hidden="true"></i></span>
                    <div>
                        <strong>{{ $log->aktor }} · {{ $log->aksi }}</strong>
                        <span>{{ $log->record }}</span>
                    </div>
                    <time datetime="{{ $log->tanggal }}T{{ $log->jam }}">{{ $log->tanggal }} · {{ substr($log->jam, 0, 5) }}</time>
                </div>
            @empty
                <div class="operations-empty-state operations-empty-state--compact">
                    <i class="ph-bold ph-notebook" aria-hidden="true"></i>
                    <strong>Belum ada aktivitas terbaru</strong>
                    <p>Perubahan data akan muncul di sini.</p>
                </div>
            @endforelse
        </div>
    </section>
@endsection
