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
<body class="d-flex flex-column h-100 layout-portal">
    <div id="app" class="d-flex flex-column h-100">
        @yield('content', $slot ?? null)
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
    @livewire('notify-toast')
    @stack('livewire-js')
</body>
</html>