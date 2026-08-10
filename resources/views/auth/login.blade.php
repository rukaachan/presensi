<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to SmartPresensi">
    @notifyCss
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>Sign in · SmartPresensi</title>
</head>

<body class="auth-body">
    <div class="notify-stack" aria-live="polite">
        @include('notify::components.notify')
        @notifyJs
    </div>

    <main class="auth-shell">
        <section class="auth-story" aria-labelledby="auth-story-title">
            <a class="auth-brand" href="{{ route('login') }}" aria-label="SmartPresensi home">
                <span class="brand-mark">SP</span>
                <span class="brand-word">Smart<span>Presensi</span></span>
            </a>

            <div class="auth-story-copy">
                <p class="eyebrow">Ruang kerja presensi sekolah</p>
                <h1 id="auth-story-title">Hari sekolah berjalan lebih baik saat kehadiran tercatat jelas.</h1>
                <p>Presensi, validasi kelas, dan administrasi sekolah dalam satu ruang kerja.</p>
            </div>

            <div class="auth-story-footer">
                <span class="auth-footer-line"></span>
                <span>Dibuat untuk operasional sekolah yang terarah</span>
            </div>
        </section>

        <section class="auth-panel" aria-labelledby="login-title">
            <div class="auth-panel-content">
                <div class="auth-panel-heading">
                    <p class="eyebrow">Selamat datang kembali</p>
                    <h2 id="login-title">Masuk ke ruang kerja Anda</h2>
                    <p>Gunakan akun sekolah untuk melanjutkan.</p>
                </div>

                <form action="{{ route('login') }}" method="POST" class="auth-form">
                    @csrf
                    <div class="field-group">
                        <label for="username">Username</label>
                        <input id="username" type="text" name="username" value="{{ old('username') }}"
                            autocomplete="username" autofocus @class(['form-control bg-background text-foreground ring-1 ring-border rounded-md', 'is-invalid' => $errors->has('username')])>
                        @error('username')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field-group">
                        <label for="password">Password</label>
                        <input id="password" type="password" name="password" autocomplete="current-password"
                            @class(['form-control bg-background text-foreground ring-1 ring-border rounded-md', 'is-invalid' => $errors->has('password')])>
                        @error('password')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="auth-submit rounded-md bg-accent text-accent-foreground ring-1 ring-accent">
                        <span>Masuk ke dashboard</span>
                        <i class="ph-bold ph-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>

                <p class="auth-note">Akses hanya tersedia untuk akun sekolah yang terdaftar.</p>
            </div>

            <div class="auth-visual rounded-lg bg-card text-card-foreground ring-1 ring-border" aria-label="Ringkasan ruang kerja presensi">
                <div class="auth-illustration" aria-hidden="true">
                    <span class="auth-illustration-card auth-illustration-card--main">
                        <i class="ph-bold ph-buildings"></i>
                    </span>
                    <span class="auth-illustration-card auth-illustration-card--top">
                        <i class="ph-bold ph-calendar-check"></i>
                    </span>
                    <span class="auth-illustration-card auth-illustration-card--bottom">
                        <i class="ph-bold ph-users-three"></i>
                    </span>
                </div>
                <span class="auth-visual-caption">Mulai dari catatan yang tepercaya</span>
            </div>
        </section>
    </main>
</body>

</html>
