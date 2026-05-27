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
            <x-mary-nav sticky full-width>
                <x-slot:brand>
                    <a href="{{ url('/') }}" class="navbar-brand">
                        <img src="{{ asset('vendor/laravel-crm/img/laravel-crm-logo.png') }}" width="215" class="block dark:hidden" />
                        <img src="{{ asset('vendor/laravel-crm/img/laravel-crm-dark-logo.png') }}" width="215" class="hidden dark:inline" />
                    </a>
                </x-slot:brand>

                <x-slot:actions>
                    <x-mary-theme-toggle class="btn btn-ghost" />
                    @auth
                        <x-mary-dropdown :label="auth()->user()->name" class="btn-neutral btn-sm" right>
                            <form method="POST" action="{{ route('laravel-crm.portal.logout') }}" x-data>
                                @csrf
                                <x-mary-menu-item href="{{ route('laravel-crm.portal.logout') }}" @click.prevent="$root.submit();" title="{{ ucfirst(__('laravel-crm::lang.logout')) }}" />
                            </form>
                        </x-mary-dropdown>
                    @else
                        <x-mary-button :label="ucfirst(__('laravel-crm::lang.login'))" :link="route('laravel-crm.portal.login')" class="btn-ghost btn-sm" />
                        @if(config('laravel-crm.portal.allow_registration', false))
                            <x-mary-button :label="ucfirst(__('laravel-crm::lang.register'))" :link="route('laravel-crm.portal.register')" class="btn-primary btn-sm text-white" />
                        @endif
                    @endauth
                </x-slot:actions>
            </x-mary-nav>

            <x-mary-main with-nav full-width>
                <x-slot:content>
                    <div class="mx-auto max-w-6xl px-4 py-8">
                        @yield('content', $slot ?? null)
                    </div>

                    <footer class="footer footer-center bg-base-100 text-base-content/60 py-4 mt-10">
                        <span>© {{ \Carbon\Carbon::now()->year }} · Powered by <a href="https://laravelcrm.com" target="_blank" rel="noopener noreferrer" class="link link-hover">Laravel CRM</a></span>
                    </footer>
                </x-slot:content>
            </x-mary-main>
        </div>

        <x-mary-toast />

        @stack('modals')
        @livewireScripts
        @stack('livewire-js')
    </body>
</html>
