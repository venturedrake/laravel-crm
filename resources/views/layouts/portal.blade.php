<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('laravel-crm::layouts.partials.meta')

    <title>{{ (config('app.name')) ? config('app.name').' - ' : null }} CRM - Client Portal</title>

    <!-- Fonts -->
    <script src="https://kit.fontawesome.com/489f6ee958.js" crossorigin="anonymous"></script>
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('vendor/laravel-crm/css/app.css') }}?v=345324356435657" rel="stylesheet">

    @livewireStyles

    @include('laravel-crm::layouts.partials.favicon')
</head>
<body class="d-flex flex-column h-100 layout-portal min-h-screen bg-base-200">
    <div id="app" class="d-flex flex-column h-100 min-h-screen flex flex-col">
        <header class="navbar bg-base-100 border-b border-base-300 px-4 py-2">
            <div class="flex-1">
                <a href="{{ url('/') }}" class="text-base font-semibold text-base-content no-underline">
                    {{ config('app.name', 'CRM') }}
                </a>
            </div>
            <div class="flex-none flex items-center gap-3 text-sm">
                @auth
                    <span class="text-base-content/70">
                        {{ ucfirst(__('laravel-crm::lang.hello')) }} {{ auth()->user()->name }}
                    </span>
                    <span class="text-base-content/40">·</span>
                    <form method="POST" action="{{ route('laravel-crm.portal.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="link link-hover text-base-content/80">
                            {{ ucfirst(__('laravel-crm::lang.logout')) }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('laravel-crm.portal.login') }}" class="link link-hover text-base-content/80">
                        {{ ucfirst(__('laravel-crm::lang.login')) }}
                    </a>
                    <span class="text-base-content/40">·</span>
                    <a href="{{ route('laravel-crm.portal.register') }}" class="link link-hover text-base-content/80">
                        {{ ucfirst(__('laravel-crm::lang.register')) }}
                    </a>
                @endauth
            </div>
        </header>

        <main class="flex-1">
            @yield('content', $slot ?? null)
        </main>

        <footer class="footer mt-auto py-3">
            <div class="container-fluid">
                <span class="text-muted">Copyright © {{ \Carbon\Carbon::now()->year }} | Powered by <a href="https://laravelcrm.com" target="_blank" rel="noopener noreferrer">Laravel CRM</a></span>
            </div>
        </footer>
    </div>
    <script src="{{ asset('vendor/laravel-crm/js/app.js') }}?v=342534624562365"></script>
    <script src="{{ asset('vendor/laravel-crm/libs/bootstrap-multiselect/bootstrap-multiselect.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bs-custom-file-input/dist/bs-custom-file-input.min.js"></script>
    @livewireScripts
    @stack('livewire-js')
</body>
</html>
