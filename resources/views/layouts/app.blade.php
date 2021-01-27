<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Laravel CRM</title>

    <!-- Scripts -->
    <script src="{{ asset('vendor/laravel-crm/js/app.js') }}?c=789789" defer></script>

    <!-- Fonts -->
    <script src="https://kit.fontawesome.com/489f6ee958.js" crossorigin="anonymous"></script>
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('vendor/laravel-crm/css/app.css') }}" rel="stylesheet">
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url(route('laravel-crm.dashboard')) }}">Laravel CRM</a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav mr-auto">

                    </ul>

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
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }} <span class="caret"></span>
                                </a>

                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('laravel-crm.logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('laravel-crm.logout') }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            <div class="container">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <ul class="nav nav-pills flex-column">
                                    <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.dashboard') === 0) ? 'active' : '' }}" aria-current="dashboard" href="{{ url(route('laravel-crm.dashboard')) }}">Dashboard</a></li>
                                    <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.leads') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.leads.index')) }}">Leads</a></li>
                                    <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.deals') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.deals.index')) }}">Deals</a></li>
                                    <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.activities') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.activities.index')) }}">Activities</a></li>
                                    <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.contacts') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.contacts.index')) }}">Contacts</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9">
                        @yield('content')
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>