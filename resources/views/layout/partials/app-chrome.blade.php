@php
    $account = Auth::user();
    $roleCode = \App\Authorization\RoleCode::forAccount($account)?->value;
    $roleName = $account->role?->name ?? __('labels.account');
    $profileModel = match ($roleCode) {
        'student', 'class_officer' => $account->student,
        'homeroom_teacher', 'duty_teacher', 'counseling_teacher' => $account->teacher,
        default => null,
    };
    $profileRoute = match ($roleCode) {
        'student' => 'student.profile.show',
        'class_officer' => 'class-officer.profile.show',
        'homeroom_teacher' => 'homeroom.profile.show',
        'duty_teacher' => 'duty-teacher.profile.show',
        'counseling_teacher' => 'counseling.profile.show',
        default => 'administration.dashboard',
    };
    $profileUrl = $profileModel
        ? route($profileRoute, ['id' => $profileModel->getKey()])
        : route('administration.dashboard');
    $dashboardRoute = match ($roleCode) {
        'student' => 'student.dashboard',
        'class_officer' => 'class-officer.dashboard',
        'homeroom_teacher' => 'homeroom.dashboard',
        'duty_teacher' => 'duty-teacher.dashboard',
        'counseling_teacher' => 'counseling.dashboard',
        'administrator' => 'administration.dashboard',
        default => 'login',
    };
    $navigation = match ($roleCode) {
        'student' => [
            ['label' => __('nav.dashboard'), 'route' => 'student.dashboard', 'active' => 'student.dashboard', 'icon' => 'ph-house'],
            ['label' => __('nav.attendance'), 'route' => 'student.attendance.create', 'active' => 'student.attendance.*', 'icon' => 'ph-calendar-check'],
            ['label' => __('nav.history'), 'route' => 'student.history.index', 'active' => 'student.history.*', 'icon' => 'ph-notebook'],
        ],
        'class_officer' => [
            ['label' => __('nav.dashboard'), 'route' => 'class-officer.dashboard', 'active' => 'class-officer.dashboard', 'icon' => 'ph-house'],
            ['label' => __('nav.attendance'), 'route' => 'class-officer.attendance.create', 'active' => 'class-officer.attendance.*', 'icon' => 'ph-calendar-check'],
            ['label' => __('nav.events'), 'route' => 'class-officer.events.index', 'active' => 'class-officer.events.*', 'icon' => 'ph-chalkboard'],
            ['label' => __('nav.history'), 'route' => 'class-officer.history.index', 'active' => 'class-officer.history.*', 'icon' => 'ph-notebook'],
        ],
        'homeroom_teacher' => [
            ['label' => __('nav.dashboard'), 'route' => 'homeroom.dashboard', 'active' => 'homeroom.dashboard', 'icon' => 'ph-house'],
            ['label' => __('nav.class_officers'), 'route' => 'homeroom.class-officers.index', 'active' => 'homeroom.class-officers.*', 'icon' => 'ph-users-three'],
            ['label' => __('nav.students'), 'route' => 'homeroom.students.index', 'active' => 'homeroom.students.*', 'icon' => 'ph-users-three'],
            ['label' => __('nav.attendance'), 'route' => 'homeroom.attendance.index', 'active' => 'homeroom.attendance.*', 'icon' => 'ph-calendar-check'],
            ['label' => __('nav.audits'), 'route' => 'homeroom.audits.index', 'active' => 'homeroom.audits.*', 'icon' => 'ph-notebook'],
        ],
        'duty_teacher' => [
            ['label' => __('nav.dashboard'), 'route' => 'duty-teacher.dashboard', 'active' => 'duty-teacher.dashboard', 'icon' => 'ph-house'],
            ['label' => __('nav.class_officers'), 'route' => 'duty-teacher.class-officers.index', 'active' => 'duty-teacher.class-officers.*', 'icon' => 'ph-users-three'],
            ['label' => __('nav.attendance'), 'route' => 'duty-teacher.attendance.index', 'active' => 'duty-teacher.attendance.*', 'icon' => 'ph-calendar-check'],
        ],
        'counseling_teacher' => [
            ['label' => __('nav.dashboard'), 'route' => 'counseling.dashboard', 'active' => 'counseling.dashboard', 'icon' => 'ph-house'],
            ['label' => __('nav.attendance'), 'route' => 'counseling.attendance.index', 'active' => 'counseling.attendance.*', 'icon' => 'ph-calendar-check'],
        ],
        'administrator' => [
            ['label' => __('nav.dashboard'), 'route' => 'administration.dashboard', 'active' => 'administration.dashboard', 'icon' => 'ph-house'],
            ['label' => __('nav.departments'), 'route' => 'administration.departments.index', 'active' => 'administration.departments.*', 'icon' => 'ph-buildings'],
            ['label' => __('nav.classrooms'), 'route' => 'administration.classrooms.index', 'active' => 'administration.classrooms.*', 'icon' => 'ph-chalkboard'],
            ['label' => __('nav.teachers'), 'route' => 'administration.teachers.index', 'active' => 'administration.teachers.*', 'icon' => 'ph-users-three'],
            ['label' => __('nav.class_officers'), 'route' => 'administration.class-officers.index', 'active' => 'administration.class-officers.*', 'icon' => 'ph-users-three'],
            ['label' => __('nav.students'), 'route' => 'administration.students.index', 'active' => 'administration.students.*', 'icon' => 'ph-users-three'],
            ['label' => __('nav.attendance'), 'route' => 'administration.attendance.index', 'active' => 'administration.attendance.*', 'icon' => 'ph-calendar-check'],
            ['label' => __('nav.audits'), 'route' => 'administration.audits.index', 'active' => 'administration.audits.*', 'icon' => 'ph-notebook'],
        ],
        default => [],
    };
    $profileName = $profileModel?->name ?? $account->username;
    $initials = strtoupper(substr((string) $profileName, 0, 1));
    $nameParts = preg_split('/\s+/', trim((string) $profileName));
    if (count($nameParts) > 1) {
        $initials .= strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
    }
@endphp

<aside class="app-sidebar bg-sidebar text-sidebar-foreground" id="sidebarMenu" aria-label="{{ __('accessibility.primary_navigation') }}">
    <div class="sidebar-inner">
        <a class="sidebar-brand" href="{{ route($dashboardRoute) }}" aria-label="{{ __('accessibility.dashboard_brand') }}">
            <span class="brand-mark">SP</span><span class="brand-word">Smart<span>Presensi</span></span>
        </a>
        <div class="sidebar-account"><a href="{{ $profileUrl }}" class="sidebar-account-link"><span class="avatar avatar--sidebar">{{ $initials }}</span><span class="sidebar-account-copy"><strong>{{ $profileName }}</strong><small>{{ $roleName }}</small></span></a></div>
        <p class="sidebar-label">{{ __('nav.primary') }}</p>
        <nav class="sidebar-nav" aria-label="{{ __('accessibility.primary_navigation') }}">
            @foreach ($navigation as $item)
                <a href="{{ route($item['route']) }}" class="sidebar-link {{ request()->routeIs($item['active']) ? 'is-active' : '' }}" @if (request()->routeIs($item['active'])) aria-current="page" @endif><i class="ph-bold {{ $item['icon'] }} sidebar-icon" aria-hidden="true"></i><span>{{ $item['label'] }}</span></a>
            @endforeach
        </nav>
        <div class="sidebar-footer"><span class="sidebar-status-dot"></span><span>{{ __('nav.local_workspace') }}</span></div>
    </div>
</aside>
<div class="sidebar-backdrop" data-sidebar-close></div>
<header class="app-topbar bg-background">
    <div class="topbar-left"><button type="button" class="sidebar-toggle" data-sidebar-toggle aria-controls="sidebarMenu" aria-expanded="false" aria-label="{{ __('accessibility.open_navigation') }}"><span></span><span></span><span></span></button><div class="topbar-context"><span class="topbar-eyebrow">SmartPresensi / {{ $roleName }}</span><h1>@yield('title')</h1></div></div>
    <div class="topbar-actions"><time class="topbar-date" datetime="{{ now('Asia/Jakarta')->toDateString() }}">{{ now('Asia/Jakarta')->locale('id')->isoFormat('D MMM YYYY') }}</time><a href="{{ $profileUrl }}" class="topbar-profile" aria-label="{{ __('accessibility.open_profile') }}"><span class="avatar avatar--topbar">{{ $initials }}</span><span class="topbar-profile-copy"><strong>{{ $profileName }}</strong><small>{{ $roleName }}</small></span></a><form action="{{ route('logout') }}" method="POST" class="logout-form">@csrf<button type="submit" class="logout-button"><span>{{ __('nav.logout') }}</span><i class="ph-bold ph-sign-out" aria-hidden="true"></i></button></form></div>
</header>
