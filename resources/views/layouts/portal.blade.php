<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @include('laravel-crm::layouts.partials.meta')

        <title>{{ config('app.name') }}{{ ! empty($title ?? null) ? ' - ' . $title : '' }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        {{ \Illuminate\Support\Facades\Vite::useBuildDirectory('vendor/laravel-crm')->withEntryPoints(['resources/css/app.css', 'resources/js/app.js']) }}

        <!-- Styles -->
        @livewireStyles

        @include('laravel-crm::layouts.partials.favicon')
    </head>
    <body class="font-sans antialiased bg-base-200">
        <div id="app" class="min-h-screen flex flex-col">
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

            <footer class="footer mt-auto py-3 px-4">
                <span class="text-base-content/60">Copyright © {{ \Carbon\Carbon::now()->year }} | Powered by <a href="https://laravelcrm.com" target="_blank" rel="noopener noreferrer" class="link link-hover">Laravel CRM</a></span>
            </footer>
        </div>

        @stack('modals')
        @livewireScripts
        @stack('livewire-js')
    </body>
</html>
