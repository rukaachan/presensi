@extends('layout.layout')
@section('judul', 'Catat presensi')
@section('page-description', 'Ambil satu foto untuk mengirimkan catatan kehadiran hari ini.')

@section('isi')
    @if (! $siswa?->id_siswa)
        <section class="operations-empty-state account-empty-state" aria-labelledby="capture-unavailable-title">
            <i class="ph-bold ph-user-circle-dashed" aria-hidden="true"></i>
            <strong id="capture-unavailable-title">Profil siswa belum siap</strong>
            <p>Akun ini belum terhubung dengan data siswa. Hubungi Tata Usaha sebelum mencatat presensi.</p>
        </section>
    @else
    <section class="capture-page" aria-labelledby="capture-title">
        <header class="capture-heading">
            <div>
                <p class="eyebrow">Presensi hari ini</p>
                <h2 id="capture-title">Satu foto. Satu catatan yang jelas.</h2>
                <p>Pastikan wajah terlihat jelas dan gunakan pencahayaan yang cukup sebelum mengirim.</p>
            </div>
            <span class="capture-step"><span>01</span> Ambil foto</span>
        </header>

        <form method="POST" action="{{ route('siswa.webcam.capture') }}" id="presensiForm" class="capture-form">
            @csrf
            <div class="capture-layout">
                <section class="capture-stage" aria-labelledby="camera-title">
                    <div class="capture-stage-heading">
                        <div>
                            <span class="capture-stage-index">A</span>
                            <h3 id="camera-title">Kamera</h3>
                        </div>
                        <span class="capture-hint">Hidup</span>
                    </div>
                    <div id="my_camera" aria-label="Pratinjau kamera"></div>
                    <button type="button" class="capture-button" onClick="takeSnapshotWithCheck()">
                        <i class="ph-bold ph-camera" aria-hidden="true"></i>
                        Ambil foto
                    </button>
                </section>

                <section class="capture-stage capture-stage--review" aria-labelledby="review-title">
                    <div class="capture-stage-heading">
                        <div>
                            <span class="capture-stage-index">B</span>
                            <h3 id="review-title">Pratinjau</h3>
                        </div>
                        <span class="capture-hint">Tinjau sebelum kirim</span>
                    </div>
                    <div id="results">Foto yang Anda ambil akan muncul di sini.</div>
                </section>
            </div>

            <input type="hidden" name="image" class="image-tag">
            <input type="hidden" name="id_siswa" value="{{ $siswa->id_siswa }}">

            <footer class="capture-footer">
                <p><i class="ph-bold ph-shield-check" aria-hidden="true"></i> Foto hanya digunakan sebagai bukti presensi.</p>
                <button type="submit" class="btn btn-primary submit-btn" disabled>
                    <span class="submit-label">Kirim presensi</span>
                    <i class="ph-bold ph-arrow-right" aria-hidden="true"></i>
                </button>
            </footer>
        </form>
    </section>
    @endif
@endsection

@section('footer')
    @if ($siswa?->id_siswa)
    <script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.25/webcam.min.js"></script>
    <script>
        Webcam.set({
            width: 490,
            height: 350,
            image_format: 'jpeg',
            jpeg_quality: 90
        });

        Webcam.attach('#my_camera');

        async function takeSnapshotWithCheck() {
            const payload = new URLSearchParams({
                id_siswa: '{{ $siswa->id_siswa }}',
                _token: '{{ csrf_token() }}'
            });

            try {
                const response = await fetch('{{ route('siswa.webcam.check_snapshot') }}', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: payload
                });
                const result = await response.json();

                if (!response.ok) {
                    throw new Error('Snapshot check failed');
                }

                if (result.exists) {
                    showErrorMessage('Presensi untuk hari ini sudah tercatat.');
                    return;
                }

                take_snapshot();
                document.querySelector('.submit-btn').removeAttribute('disabled');
            } catch (error) {
                showErrorMessage('Pemeriksaan presensi gagal. Silakan coba lagi.');
            }
        }

        function take_snapshot() {
            Webcam.snap(function(data_uri) {
                document.querySelector('.image-tag').value = data_uri;
                document.getElementById('results').innerHTML = '<img src="' + data_uri + '" alt="Pratinjau foto presensi">';
            });
        }

        function showErrorMessage(message) {
            window.swal.fire({
                icon: 'error',
                title: 'Presensi belum dikirim',
                text: message,
                confirmButtonText: 'Mengerti'
            });
        }

        document.getElementById('presensiForm').addEventListener('submit', function() {
            const button = this.querySelector('.submit-btn');
            button.disabled = true;
            button.querySelector('.submit-label').textContent = 'Menyimpan...';
        });
    </script>
    @endif
@endsection
