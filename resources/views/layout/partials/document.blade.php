<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ __('common.app_description') }}">
    <meta name="theme-color" content="#ffffff">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @notifyCss
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>@yield('title', 'SmartPresensi') · SmartPresensi</title>
</head>

<body class="app-body" data-route="{{ request()->route()?->getName() }}">
    <a class="skip-link" href="#main-content">{{ __('common.skip_to_content') }}</a>

    <div class="notify-stack" aria-live="polite">
        @include('notify::components.notify')
        @notifyJs
    </div>

    <div class="app-shell">
        @include('layout.partials.app-chrome')

        <main id="main-content" class="app-main">
            <div class="page-container">
                @if (! request()->routeIs('*.dashboard'))
                    <header class="page-heading" aria-labelledby="page-title">
                        <div>
                            <p class="page-heading-kicker">{{ __('common.workspace') }}</p>
                            <h2 id="page-title">@yield('title')</h2>
                            @hasSection('page-description')
                                <p class="page-heading-description">@yield('page-description')</p>
                            @endif
                        </div>
                        @yield('page-actions')
                    </header>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @yield('footer')
</body>

</html>
