<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ (config('app.name')) ? config('app.name').' - ' : null }} CRM</title>

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
        <div class="min-h-screen flex flex-col justify-center items-center p-4">
            <div class="mb-6">
                <a href="{{ route('laravel-crm.login') }}">
                    <img src="{{ asset('vendor/laravel-crm/img/laravel-crm-logo.png') }}" width="215" class="block dark:hidden" alt="CRM" />
                    <img src="{{ asset('vendor/laravel-crm/img/laravel-crm-dark-logo.png') }}" width="215" class="hidden dark:inline" alt="CRM" />
                </a>
            </div>

            <div class="w-full max-w-md">
                {{ $slot }}
            </div>

            <div class="mt-6 flex items-center gap-3 text-sm text-base-content/50">
                <span>&copy; {{ date('Y') }} | Powered by <a href="https://laravelcrm.com" target="_blank" rel="noopener noreferrer">Laravel CRM</a></span>
                <x-mary-theme-toggle class="btn btn-ghost btn-xs" />
            </div>
        </div>

        <x-mary-toast />
        @livewireScripts
    </body>
</html>


