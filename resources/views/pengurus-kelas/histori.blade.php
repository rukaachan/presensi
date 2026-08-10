@extends('layout.layout')
@section('judul', 'Histori presensi kelas')
@section('page-description', 'Telusuri dan unduh catatan kehadiran yang dikelola untuk kelas Anda.')

@section('isi')
    <section class="history-workspace" aria-labelledby="history-title">
        <header class="history-heading">
            <div>
                <p class="eyebrow">Catatan kelas</p>
                <h2 id="history-title">Riwayat presensi kelas.</h2>
            </div>
            <span class="history-count">{{ $filter->count() }} catatan</span>
        </header>

        <form action="" method="get" id="form" class="history-filters">
            <div class="history-filter-group">
                <label for="bulan">Bulan</label>
                <select class="form-select" name="bulan" id="bulan" onchange="this.form.submit()">
                    <option value="" {{ $selectedMonth === null ? 'selected' : '' }}>Semua bulan</option>
                    @foreach ($bulanList as $index => $bulan)
                        <option value="{{ $index }}" {{ $selectedMonth == $index ? 'selected' : '' }}>{{ $bulan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="history-filter-group">
                <label for="minggu">Rentang minggu</label>
                <select class="form-select" name="minggu" id="minggu" onchange="this.form.submit()">
                    <option value="" {{ $selectedWeek === null ? 'selected' : '' }}>Semua minggu</option>
                    @for ($i = 1; $i <= 4; $i++)
                        <option value="{{ $i }}" {{ $selectedWeek == $i ? 'selected' : '' }}>Minggu ke-{{ $i }}</option>
                    @endfor
                </select>
            </div>
            <button type="button" class="btn btn-secondary history-export" id="downloadPDF">
                <i class="ph-bold ph-download-simple" aria-hidden="true"></i>
                Unduh PDF
            </button>
        </form>

        <div class="table-scroll" tabindex="0" aria-label="Tabel histori presensi kelas">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th scope="col">No</th>
                        <th scope="col">NIS</th>
                        <th scope="col">Nama siswa</th>
                        <th scope="col">Jenis kelamin</th>
                        <th scope="col">Tanggal</th>
                        <th scope="col">Kehadiran</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filter as $record)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $record->nis }}</td>
                            <td><strong class="table-primary-text">{{ $record->nama_siswa }}</strong></td>
                            <td>{{ $record->jenis_kelamin }}</td>
                            <td>{{ $record->tanggal }}</td>
                            <td><span class="status-badge status-badge--{{ $record->status_kehadiran }}">{{ ucfirst($record->status_kehadiran) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="table-empty-state">
                                    <i class="ph-bold ph-calendar-blank" aria-hidden="true"></i>
                                    <strong>Belum ada catatan pada filter ini</strong>
                                    <span>Coba pilih bulan atau rentang minggu yang lain.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@section('footer')
    <script type="module">
        document.getElementById('downloadPDF')?.addEventListener('click', () => {
            const form = document.getElementById('form');
            if (!form) return;
            form.action = '{{ route('pengurus-kelas.presensi.pdf') }}';
            form.submit();
        });
    </script>
@endsection
