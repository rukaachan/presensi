@php
    $account = Auth::user();
    $role = (int) $account->id_role;

    $roleName = match ($role) {
        1 => 'Siswa',
        2 => 'Wali kelas',
        3 => 'Pengurus kelas',
        4 => 'Guru piket',
        5 => 'Guru BK',
        6 => 'Tata usaha',
        default => 'Akun',
    };

    // Keep the shell query-free; profile details load only on the profile screen.
    $profileName = $account->username;
    $profilePhoto = null;
    $profileDirectory = null;

    $profileUrl = match ($role) {
        1 => route('siswa.profil.detail', ['id' => $account->id_akun]),
        2 => route('wali-kelas.profil.detail', ['id' => $account->id_akun]),
        3 => route('pengurus-kelas.profil.detail', ['id' => $account->id_akun]),
        4 => route('guru-piket.profil.detail', ['id' => $account->id_akun]),
        5 => route('guru-bk.profil.detail', ['id' => $account->id_akun]),
        default => route('tata-usaha.dashboard'),
    };

    $dashboardRoute = match ($role) {
        1 => 'siswa.dashboard',
        2 => 'wali-kelas.dashboard',
        3 => 'pengurus-kelas.dashboard',
        4 => 'guru-piket.dashboard',
        5 => 'guru-bk.dashboard',
        6 => 'tata-usaha.dashboard',
        default => 'login',
    };

    $navigation = match ($role) {
        1 => [
            ['label' => 'Dashboard', 'route' => 'siswa.dashboard', 'active' => 'siswa.dashboard', 'icon' => 'ph-house'],
            ['label' => 'Presensi', 'route' => 'siswa.presensi.index', 'active' => 'siswa.presensi.*', 'icon' => 'ph-calendar-check'],
            ['label' => 'Histori presensi', 'route' => 'siswa.histori.index', 'active' => 'siswa.histori.*', 'icon' => 'ph-notebook'],
        ],
        2 => [
            ['label' => 'Dashboard', 'route' => 'wali-kelas.dashboard', 'active' => 'wali-kelas.dashboard', 'icon' => 'ph-house'],
            ['label' => 'Pengurus kelas', 'route' => 'wali-kelas.pengurus-kelas.index', 'active' => 'wali-kelas.pengurus-kelas.*', 'icon' => 'ph-users-three'],
            ['label' => 'Siswa', 'route' => 'wali-kelas.siswa.index', 'active' => 'wali-kelas.siswa.*', 'icon' => 'ph-users-three'],
            ['label' => 'Presensi', 'route' => 'wali-kelas.presensi-siswa.index', 'active' => 'wali-kelas.presensi-siswa.*', 'icon' => 'ph-calendar-check'],
            ['label' => 'Logs', 'route' => 'wali-kelas.logs.index', 'active' => 'wali-kelas.logs.*', 'icon' => 'ph-notebook'],
        ],
        3 => [
            ['label' => 'Dashboard', 'route' => 'pengurus-kelas.dashboard', 'active' => 'pengurus-kelas.dashboard', 'icon' => 'ph-house'],
            ['label' => 'Presensi', 'route' => 'pengurus-kelas.presensi.index', 'active' => 'pengurus-kelas.presensi.*', 'icon' => 'ph-calendar-check'],
            ['label' => 'Validasi kelas', 'route' => 'pengurus-kelas.kelas.index', 'active' => 'pengurus-kelas.kelas.*', 'icon' => 'ph-chalkboard'],
            ['label' => 'Histori presensi', 'route' => 'pengurus-kelas.histori.index', 'active' => 'pengurus-kelas.histori.*', 'icon' => 'ph-notebook'],
        ],
        4 => [
            ['label' => 'Dashboard', 'route' => 'guru-piket.dashboard', 'active' => 'guru-piket.dashboard', 'icon' => 'ph-house'],
            ['label' => 'Pengurus kelas', 'route' => 'guru-piket.pengurus-kelas.index', 'active' => 'guru-piket.pengurus-kelas.*', 'icon' => 'ph-users-three'],
            ['label' => 'Presensi', 'route' => 'guru-piket.presensi.index', 'active' => 'guru-piket.presensi.*', 'icon' => 'ph-calendar-check'],
        ],
        5 => [
            ['label' => 'Dashboard', 'route' => 'guru-bk.dashboard', 'active' => 'guru-bk.dashboard', 'icon' => 'ph-house'],
            ['label' => 'Presensi', 'route' => 'guru-bk.presensi.index', 'active' => 'guru-bk.presensi.*', 'icon' => 'ph-calendar-check'],
        ],
        6 => [
            ['label' => 'Dashboard', 'route' => 'tata-usaha.dashboard', 'active' => 'tata-usaha.dashboard', 'icon' => 'ph-house'],
            ['label' => 'Jurusan', 'route' => 'tata-usaha.jurusan.index', 'active' => 'tata-usaha.jurusan.*', 'icon' => 'ph-buildings'],
            ['label' => 'Kelas', 'route' => 'tata-usaha.kelas.index', 'active' => 'tata-usaha.kelas.*', 'icon' => 'ph-chalkboard', 'params' => ['filter_status' => 'aktif']],
            ['label' => 'Guru', 'route' => 'tata-usaha.guru.index', 'active' => 'tata-usaha.guru.*', 'icon' => 'ph-users-three'],
            ['label' => 'Pengurus kelas', 'route' => 'tata-usaha.pengurus-kelas.index', 'active' => 'tata-usaha.pengurus-kelas.*', 'icon' => 'ph-users-three'],
            ['label' => 'Siswa', 'route' => 'tata-usaha.siswa.index', 'active' => 'tata-usaha.siswa.*', 'icon' => 'ph-users-three', 'params' => ['filter_status' => 'aktif']],
            ['label' => 'Presensi', 'route' => 'tata-usaha.presensi.index', 'active' => 'tata-usaha.presensi.*', 'icon' => 'ph-calendar-check'],
            ['label' => 'Logs', 'route' => 'tata-usaha.logs.index', 'active' => 'tata-usaha.logs.*', 'icon' => 'ph-notebook'],
        ],
        default => [],
    };


    $initials = strtoupper(substr((string) $profileName, 0, 1));
    $nameParts = preg_split('/\s+/', trim((string) $profileName));
    if (count($nameParts) > 1) {
        $initials .= strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
    }
@endphp

<aside class="app-sidebar bg-sidebar text-sidebar-foreground" id="sidebarMenu" aria-label="Primary navigation">
    <div class="sidebar-inner">
        <a class="sidebar-brand" href="{{ route($dashboardRoute) }}" aria-label="SmartPresensi dashboard">
            <span class="brand-mark">SP</span>
            <span class="brand-word">Smart<span>Presensi</span></span>
        </a>

        <div class="sidebar-account">
            <a href="{{ $profileUrl }}" class="sidebar-account-link">
                <span class="avatar avatar--sidebar">
                    @if ($profilePhoto && $profileDirectory)
                        <img src="{{ asset($profileDirectory . '/' . $profilePhoto) }}" alt="">
                    @else
                        {{ $initials }}
                    @endif
                </span>
                <span class="sidebar-account-copy">
                    <strong>{{ $profileName }}</strong>
                    <small>{{ $roleName }}</small>
                </span>
            </a>
        </div>

        <p class="sidebar-label">Menu utama</p>
        <nav class="sidebar-nav" aria-label="Menu utama">
            @foreach ($navigation as $item)
                <a href="{{ route($item['route'], $item['params'] ?? []) }}"
                    class="sidebar-link {{ request()->routeIs($item['active']) ? 'is-active' : '' }}"
                    @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                    <i class="ph-bold {{ $item['icon'] ?? 'ph-squares-four' }} sidebar-icon" aria-hidden="true"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="sidebar-footer">
            <span class="sidebar-status-dot"></span>
            <span>Ruang kerja lokal</span>
        </div>
    </div>
</aside>

<div class="sidebar-backdrop" data-sidebar-close></div>

<header class="app-topbar bg-background">
    <div class="topbar-left">
        <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-controls="sidebarMenu"
            aria-expanded="false" aria-label="Open navigation">
            <span></span><span></span><span></span>
        </button>
        <div class="topbar-context">
            <span class="topbar-eyebrow">SmartPresensi / {{ $roleName }}</span>
            <h1>@yield('judul')</h1>
        </div>
    </div>

    <div class="topbar-actions">
        <time class="topbar-date" datetime="{{ now('Asia/Jakarta')->toDateString() }}">{{ now('Asia/Jakarta')->locale('id')->isoFormat('D MMM YYYY') }}</time>
        <a href="{{ $profileUrl }}" class="topbar-profile" aria-label="Open profile">
            <span class="avatar avatar--topbar">
                @if ($profilePhoto && $profileDirectory)
                    <img src="{{ asset($profileDirectory . '/' . $profilePhoto) }}" alt="">
                @else
                    {{ $initials }}
                @endif
            </span>
            <span class="topbar-profile-copy">
                <strong>{{ $profileName }}</strong>
                <small>{{ $roleName }}</small>
            </span>
        </a>
        <form action="{{ route('logout') }}" method="POST" class="logout-form">
            @csrf
            <button type="submit" class="logout-button">
                <span>Keluar</span><i class="ph-bold ph-sign-out" aria-hidden="true"></i>
            </button>
        </form>
    </div>
</header>
