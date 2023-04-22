<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('laravel-crm::layouts.partials.meta')

    <title>{{ (config('app.name')) ? config('app.name').' - ' : null }} CRM</title>

    <!-- Fonts -->
    <script src="https://kit.fontawesome.com/489f6ee958.js" crossorigin="anonymous"></script>
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    
    <!-- Styles -->
    <link href="{{ asset('vendor/laravel-crm/css/app.css') }}?v=76546474567678" rel="stylesheet">

    @livewireStyles

    @include('laravel-crm::layouts.partials.favicon')
</head>
<body class="d-flex flex-column h-100">
    <div id="app" class="d-flex flex-column h-100">
        @auth
        <header>
            <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm fixed-top">
                <div class="container-fluid">
                    <a class="navbar-brand" href="{{ url(route('laravel-crm.dashboard')) }}" data-toggle="tooltip" data-placement="bottom" title="v{{ config('laravel-crm.version') }}">{{ config('app.name', 'Laravel ') }} CRM</a>
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <!-- Left Side Of Navbar -->
                        @include('laravel-crm::layouts.partials.search')

                        <!-- Right Side Of Navbar -->
                        <ul class="navbar-nav ml-auto">
                            <!-- Authentication Links -->
                            @guest
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('laravel-crm.login') }}">{{ __('Login') }}</a>
                                </li>
                                @if (Route::has('laravel-crm.register'))
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('laravel-crm.register') }}">{{ __('Register') }}</a>
                                    </li>
                                @endif
                            @else
                                @include('laravel-crm::layouts.partials.nav-user')  
                            @endguest
                        </ul>
                    </div>
                </div>
            </nav>
        </header>
        @endauth

       <main role="main" class="flex-shrink-0">
            <div class="container-fluid">
                <div class="row">
                    @auth
                    <div class="col col-md-2">
                       @include('laravel-crm::layouts.partials.nav')
                    </div>
                    @endauth
                    <div class="col col-md-10">
                        @include('flash::message')
                        @yield('content', $slot ?? null)
                    </div>
                </div>
            </div>
        </main>
        <footer class="footer mt-auto py-3">
            <div class="container-fluid">
                <span class="text-muted">Copyright © {{ \Carbon\Carbon::now()->year }} | Powered by <a href="https://laravelcrm.com" target="_blank" rel="noopener noreferrer">Laravel CRM</a></span>
            </div>
        </footer>
    </div>
    <script src="{{ asset('vendor/laravel-crm/js/app.js') }}?v=2343423423423"></script>
    <script src="{{ asset('vendor/laravel-crm/libs/bootstrap-multiselect/bootstrap-multiselect.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bs-custom-file-input/dist/bs-custom-file-input.min.js"></script>
    @livewireScripts
    @livewire('notify-toast')
    @stack('livewire-js')
</body>
</html>
