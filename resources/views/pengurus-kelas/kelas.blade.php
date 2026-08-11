@extends('layout.layout')
@section('judul', 'Validasi kelas')
@section('page-description', 'Tentukan status validasi pada setiap catatan kehadiran kelas.')

@section('isi')
    <section class="validation-page" aria-labelledby="validation-title">
        <header class="workspace-intro"><div><p class="eyebrow">Usulan kelas</p><h2 id="validation-title">Tandai catatan yang perlu ditinjau.</h2><p>Pilih sesi opsional dan kirim usulan status. Guru piket atau admin akan menetapkan keputusan akhir.</p></div><span class="workspace-index">02 / Pengurus</span></header>
        <form id="validasiForm" action="{{ route('pengurus-kelas.kelas.validasi.update') }}" method="POST" class="validation-form">@csrf<div class="validation-toolbar"><label for="waktu_validasi"><span>Waktu validasi</span><select id="waktu_validasi" name="waktu_validasi" class="form-select">@forelse ($validationSessions as $session)<option value="{{ $session->legacy_code }}" @selected($selectedValidationCode === $session->legacy_code)>{{ $session->label }}</option>@empty<option value="" disabled selected>Belum ada sesi validasi</option>@endforelse</select></label><div class="validation-actions"><button type="button" class="btn btn-secondary" id="downloadPDF"><i class="ph-bold ph-download-simple" aria-hidden="true"></i> Unduh PDF</button><button type="submit" class="btn btn-primary"><i class="ph-bold ph-paper-plane-tilt" aria-hidden="true"></i> Kirim usulan</button></div></div><div class="table-scroll" tabindex="0" aria-label="Tabel validasi kelas"><table class="table table-bordered validation-table"><thead><tr><th scope="col">No</th><th scope="col">Siswa</th><th scope="col" colspan="4">Pilih status</th></tr><tr><th></th><th></th><th>Hadir</th><th>Izin</th><th>Alpha</th><th>Pulang</th></tr></thead><tbody>
                    @forelse ($data as $item)
                        @php $rowIndex = $loop->iteration; @endphp
                        <tr class="data-row" data-waktu-validasi="{{ $item->waktu_validasi }}">
                            <td>{{ $rowIndex }}</td>
                            <td>
                                <strong class="table-primary-text">{{ $item->nama_siswa }}</strong>
                                <span class="validation-student-meta">{{ $item->nis }}</span>
                                <input type="hidden" name="id_pengurus[{{ $rowIndex }}]" value="{{ $item->id_pengurus }}">
                                <input type="hidden" name="id_presensi[{{ $rowIndex }}]" value="{{ $item->id_presensi }}">
                            </td>
                            @foreach (['hadir', 'izin', 'alpha', 'pulang'] as $status)
                                <td>
                                    <label class="validation-choice">
                                        <input type="radio" name="status_validasi[{{ $rowIndex }}][]" value="{{ $status }}" {{ $item->status_validasi === $status ? 'checked' : '' }}>
                                        <span>{{ ucfirst($status) }}</span>
                                    </label>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="table-empty-state"><i class="ph-bold ph-seal-check" aria-hidden="true"></i><strong>Belum ada catatan untuk divalidasi</strong><span>Catatan presensi kelas akan muncul setelah dikirim.</span></div></td></tr>
                    @endforelse
                </tbody></table></div></form>
    </section>
@endsection
@section('footer')<script type="module">const form = document.getElementById('validasiForm'); const rows = [...document.querySelectorAll('.data-row')]; const filterRows = () => { const value = document.getElementById('waktu_validasi').value; rows.forEach((row) => { row.hidden = row.dataset.waktuValidasi && row.dataset.waktuValidasi !== value; }); }; document.getElementById('waktu_validasi')?.addEventListener('change', filterRows); filterRows(); document.getElementById('downloadPDF')?.addEventListener('click', () => { form.action = '{{ route('pengurus-kelas.kelas.pdf') }}'; form.submit(); });</script>@endsection
