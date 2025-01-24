<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @include('laravel-crm::layouts.partials.meta')

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
    <body class="font-sans antialiased">
    
    <x-mary-nav sticky full-width>
        <x-slot:brand>
            <label for="main-drawer" class="lg:hidden mr-3">
                <x-mary-icon name="o-bars-3" class="cursor-pointer" />
            </label>
            <x-mary-popover>
                <x-slot:trigger>
                    <a class="navbar-brand text-2xl font-extrabold" href="{{ url(route('laravel-crm.dashboard')) }}" @can('view crm updates')data-toggle="tooltip" data-placement="bottom" title="v{{ config('laravel-crm.version') }}"@endcan>{{ config('app.name', 'Laravel ') }} CRM</a>
                </x-slot:trigger>
                <x-slot:content>
                    Version {{ config('laravel-crm.version') }} <br>
                    @if(\VentureDrake\LaravelCrm\Models\Setting::where('name','version')->first()->value < \VentureDrake\LaravelCrm\Models\Setting::where('name','version_latest')->first()->value)
                        <x-mary-badge value="Upgrade Available" class="badge-success" />
                    @else
                        <x-mary-badge value="Latest Version" class="badge-primary" />
                    @endif
                </x-slot:content>
            </x-mary-popover>
        </x-slot:brand>
        
        <x-slot:actions>
            <x-mary-input icon="o-magnifying-glass" placeholder="Search..." />
            <x-mary-button label="Messages" icon="o-envelope" link="###" class="btn-ghost btn-sm" responsive />
            <x-mary-button label="Notifications" icon="o-bell" link="###" class="btn-ghost btn-sm" responsive />
            <x-mary-theme-toggle class="btn btn-ghost" />
            @if (class_exists('\Laravel\Jetstream\Jetstream') && Laravel\Jetstream\Jetstream::managesProfilePhotos())
                <x-mary-avatar :image="auth()->user()->profile_photo_url" alt="{{ Auth::user()->name }}" />
            @else    
            <x-mary-dropdown label="{{ auth()->user()->name }}" class="btn-warning" right>
                @if(Route::has('profile.show'))
                    <x-mary-menu-item href="{{ route('profile.show') }}" title="{{ __('Profile') }}" />
                @endif
                @if (class_exists('\Laravel\Jetstream\Jetstream') && Laravel\Jetstream\Jetstream::hasApiFeatures())
                    <x-mary-menu-item href="{{ route('api-tokens.index') }}" title="{{ __('API Tokens') }}" />
                @endif
                    <x-mary-menu-separator />
                    <form method="POST" action="{{ route('logout') }}" x-data>
                        @csrf
                        <x-mary-menu-item href="{{ route('logout') }}" @click.prevent="$root.submit();"  title="{{ __('Log Out') }}" />
                    </form>
            </x-mary-dropdown>
            @endif    
        </x-slot:actions>
    </x-mary-nav>
    
    <x-mary-main with-nav full-width>
        
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-200">

            {{-- Activates the menu item when a route matches the `link` property --}}
            <x-mary-menu activate-by-route>
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.dashboard')) }}" icon="o-home" link="{{ url(route('laravel-crm.dashboard')) }}" />
                <x-mary-menu-separator />
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.leads')) }}" icon="o-home" link="{{ url(route('laravel-crm.leads.index')) }}" />
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.deals')) }}" icon="o-home" link="{{ url(route('laravel-crm.deals.index')) }}" />
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.quotes')) }}" icon="o-home" link="{{ url(route('laravel-crm.quotes.index')) }}" />
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.activities')) }}" icon="o-home" link="{{ url(route('laravel-crm.activities.index')) }}" />
                <x-mary-menu-separator />
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.orders')) }}" icon="o-home" link="{{ url(route('laravel-crm.orders.index')) }}" />
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.invoices')) }}" icon="o-home" link="{{ url(route('laravel-crm.quotes.index')) }}" />
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.deliveries')) }}" icon="o-home" link="{{ url(route('laravel-crm.deliveries.index')) }}" />
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.purchase_orders')) }}" icon="o-home" link="{{ url(route('laravel-crm.purchase-orders.index')) }}" />
                <x-mary-menu-separator />
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.customers')) }}" icon="o-home" link="{{ url(route('laravel-crm.customers.index')) }}" />
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.organizations')) }}" icon="o-home" link="{{ url(route('laravel-crm.organizations.index')) }}" />
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.people')) }}" icon="o-home" link="{{ url(route('laravel-crm.people.index')) }}" />
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.users')) }}" icon="o-home" link="{{ url(route('laravel-crm.users.index')) }}" />
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.teams')) }}" icon="o-home" link="{{ url(route('laravel-crm.teams.index')) }}" />
                <x-mary-menu-separator />
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.products')) }}" icon="o-home" link="{{ url(route('laravel-crm.products.index')) }}" />
                <x-mary-menu-separator />
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.settings')) }}" icon="o-home" link="{{ url(route('laravel-crm.settings.edit')) }}" />
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.updates')) }}" icon="o-home" link="{{ url(route('laravel-crm.updates.index')) }}" />
                <x-mary-menu-separator />
                <x-mary-menu-item title="Messages" icon="o-envelope" link="###" />
                <x-mary-menu-sub title="Settings" icon="o-cog-6-tooth">
                    <x-mary-menu-item title="Wifi" icon="o-wifi" link="####" />
                    <x-mary-menu-item title="Archives" icon="o-archive-box" link="####" />
                </x-mary-menu-sub>
            </x-mary-menu>
        </x-slot:sidebar>
        <x-slot:content>
            <!-- Page Heading -->
            @if (isset($header))
                {{ $header }}
            @endif
            {{ $slot ?? null }}
        </x-slot:content>
    </x-mary-main>
    
    <x-mary-toast />

    @stack('modals')

    @livewireScripts
    <!--@livewire('notify-toast')-->
    @stack('livewire-js')
    </body>
</html>
