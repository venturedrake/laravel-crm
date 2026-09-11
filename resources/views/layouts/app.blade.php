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

        {{-- Flatpickr  --}}
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        
        <!-- Styles -->
        @livewireStyles

        @include('laravel-crm::layouts.partials.favicon')
    </head>
    <body class="font-sans antialiased bg-base-200">
    
    <x-mary-nav sticky full-width>
        <x-slot:brand>
            <label for="main-drawer" class="lg:hidden mr-3">
                <x-mary-icon name="o-bars-3" class="cursor-pointer" />
            </label>
            <x-mary-popover>
                <x-slot:trigger>
                    <a class="navbar-brand text-2xl font-extrabold" href="{{ url(route('laravel-crm.dashboard')) }}" @can('view crm updates')data-toggle="tooltip" data-placement="bottom" title="v{{ config('laravel-crm.version') }}"@endcan><img src="{{ asset('vendor/laravel-crm/img/laravel-crm-logo.png') }}" width="215" class="block dark:hidden" /> <img src="{{ asset('vendor/laravel-crm/img/laravel-crm-dark-logo.png') }}" width="215" class="hidden dark:inline" /> </a>
                </x-slot:trigger>
                <x-slot:content>
                    Version {{ config('laravel-crm.version') }} <br>
                    @php
                        // Off the memoised settings map, not two raw queries on
                        // every page render. version_compare rather than a
                        // string compare: '2.2.0' < '2.10.0' is false
                        // lexicographically, so the badge lied once the minor
                        // hit double digits.
                        $currentVersion = app('laravel-crm.settings')->get('version');
                        $latestVersion = app('laravel-crm.settings')->get('version_latest');
                    @endphp
                    @if($currentVersion && $latestVersion && version_compare($currentVersion, $latestVersion, '<'))
                        <x-mary-badge value="Upgrade Available" class="badge-success" />
                    @else
                        <x-mary-badge value="Latest Version" class="badge-primary" />
                    @endif
                </x-slot:content>
            </x-mary-popover>
        </x-slot:brand>
        
        <x-slot:actions>
            {{--<x-mary-input icon="o-magnifying-glass" placeholder="Search..." />--}}
            {{--<x-mary-button label="Messages" icon="o-envelope" link="###" class="btn-ghost btn-sm" responsive />
            <x-mary-button label="Notifications" icon="o-bell" link="###" class="btn-ghost btn-sm" responsive />--}}
            <x-mary-theme-toggle class="btn btn-ghost" />
            @if (config('laravel-crm.teams'))
            @php
                $crmUser = Auth::user();
                $crmCurrentTeam = null;
                if ($crmUser) {
                    try { $crmCurrentTeam = $crmUser->currentTeam; } catch (\Throwable $e) {}
                }
                if ($crmUser && ! $crmCurrentTeam && method_exists($crmUser, 'crmTeams')) {
                    $crmCurrentTeam = $crmUser->crmTeams()->first();
                }

                $crmAllTeams = collect();
                if ($crmUser) {
                    if (method_exists($crmUser, 'allTeams')) {
                        $crmAllTeams = collect($crmUser->allTeams());
                    } elseif (method_exists($crmUser, 'crmTeams')) {
                        $crmAllTeams = $crmUser->crmTeams()->get();
                    }
                }

                $crmIsCurrentTeam = function ($team) use ($crmUser, $crmCurrentTeam) {
                    if ($crmUser && method_exists($crmUser, 'isCurrentTeam')) {
                        return $crmUser->isCurrentTeam($team);
                    }
                    return $crmCurrentTeam && $crmCurrentTeam->id === $team->id;
                };

                $newTeamRoute = null;
                foreach (['laravel-crm.host-teams.create', 'teams.create', 'laravel-crm.teams.create'] as $candidate) {
                    if (Route::has($candidate)) {
                        $newTeamRoute = $candidate;
                        break;
                    }
                }
            @endphp
            @if ($crmCurrentTeam || $crmAllTeams->isNotEmpty() || $newTeamRoute)
                <x-mary-dropdown label="{{ $crmCurrentTeam?->name ?? __('Enterprises') }}" class="btn-neutral btn-sm" right>
                    <x-mary-menu-item title="{{ __('Enterprises') }}" class="menu-title" />
                    @if (Route::has('current-team.update'))
                        @foreach ($crmAllTeams as $team)
                            <li>
                                <form method="POST" action="{{ route('current-team.update') }}" class="!p-0">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="team_id" value="{{ $team->id }}">
                                    <button type="submit" class="my-0.5 py-1.5 px-4 w-full text-left hover:text-inherit whitespace-nowrap flex items-center gap-2">
                                        @if ($crmIsCurrentTeam($team))
                                            <x-mary-icon name="o-check" class="w-5 h-5" />
                                        @else
                                            <span class="w-5"></span>
                                        @endif
                                        <span class="whitespace-nowrap truncate">{{ $team->name }}</span>
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    @endif
                    @if ($newTeamRoute)
                        <x-mary-menu-separator />
                        <li>
                            <a href="{{ route($newTeamRoute) }}" class="my-0.5 py-1.5 px-4 hover:text-inherit whitespace-nowrap">
                                <span class="block py-0.5"><x-mary-icon name="o-plus" class="mb-0.5" /></span>
                                <span class="whitespace-nowrap truncate">{{ __('New enterprise') }}</span>
                            </a>
                        </li>
                    @endif
                </x-mary-dropdown>
            @endif
            @endif
            @if (class_exists('\Laravel\Jetstream\Jetstream') && Laravel\Jetstream\Jetstream::managesProfilePhotos())
                <x-mary-avatar :image="auth()->user()->profile_photo_url" alt="{{ Auth::user()->name }}" />
            @else    
            <x-mary-dropdown label="{{ auth()->user()->name }}" class="btn-neutral btn-sm" right>
                @if(Route::has('profile.show'))
                    <x-mary-menu-item href="{{ route('profile.show') }}" title="{{ __('Profile') }} ({{ __('Host') }})" />
                @else
                    <x-mary-menu-item href="{{ route('laravel-crm.profile') }}" title="{{ ucfirst(__('laravel-crm::lang.profile')) }}" />
                @endif
              {{--  @if (class_exists('\Laravel\Jetstream\Jetstream') && Laravel\Jetstream\Jetstream::hasApiFeatures())
                    <x-mary-menu-item href="{{ route('api-tokens.index') }}" title="{{ __('API Tokens') }}" />
                @endif--}}
                    <x-mary-menu-separator />
                    <form method="POST" action="{{ route('laravel-crm.logout') }}" x-data>
                        @csrf
                        <x-mary-menu-item href="{{ route('laravel-crm.logout') }}" @click.prevent="$root.submit();"  title="{{ __('Log Out') }}" />
                    </form>
            </x-mary-dropdown>
            @endif    
        </x-slot:actions>
    </x-mary-nav>
    
    <x-mary-main with-nav full-width>
        
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-200">

            {{-- Activates the menu item when a route matches the `link` property --}}
            <x-mary-menu activate-by-route class="w-full bg-base-100 rounded-none pt-3">
                <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.dashboard')) }}" icon="bxs.dashboard" link="{{ url(route('laravel-crm.dashboard')) }}" />

                <hr class="my-2 border-t-[length:var(--border)] border-base-content/10">

                @can('view crm tasks')
                    <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.tasks')) }}" icon="fas.tasks" link="{{ url(route('laravel-crm.tasks.index')) }}" />
                @endcan

                @haschatenabled
                    @can('view crm chat')
                        <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.chat')) }}" icon="fas.comments" link="{{ url(route('laravel-crm.chat.index')) }}" />
                    @endcan
                @endhaschatenabled

                @hasemailmarketingenabled
                    @can('view crm email-campaigns')
                        <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.email')) }}" icon="fas.envelope" link="{{ url(route('laravel-crm.email-campaigns.index')) }}" />
                    @endcan
                @endhasemailmarketingenabled

                @hassmsmarketingenabled
                    @can('view crm sms-campaigns')
                        <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.sms')) }}" icon="fas.sms" link="{{ url(route('laravel-crm.sms-campaigns.index')) }}" />
                    @endcan
                @endhassmsmarketingenabled

                @canany(['view crm activities', 'view crm tasks', 'view crm notes'])
                    <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.activity')) }}" icon="fas.timeline" link="{{ url(route('laravel-crm.activities.index')) }}" />
                @endcanany

                <hr class="my-2 border-t-[length:var(--border)] border-base-content/10">

                @hasleadsenabled
                    @can('view crm leads')
                        <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.leads')) }}" icon="fas.crosshairs" link="{{ url(route('laravel-crm.leads.index')) }}" />
                    @endcan
                @endhasleadsenabled

                @hasdealsenabled
                    @can('view crm deals')
                        <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.deals')) }}" icon="fas.dollar-sign" link="{{ url(route('laravel-crm.deals.index')) }}" />
                    @endcan
                @endhasdealsenabled

                @hasquotesenabled
                    @can('view crm quotes')
                        <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.quotes')) }}" icon="fas.file-lines" link="{{ url(route('laravel-crm.quotes.index')) }}" />
                    @endcan
                @endhasquotesenabled

                <hr class="my-2 border-t-[length:var(--border)] border-base-content/10">

                @hasordersenabled
                    @can('view crm orders')
                        <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.orders')) }}" icon="fas.shopping-cart" link="{{ url(route('laravel-crm.orders.index')) }}" />
                    @endcan
                @endhasordersenabled

                @hasinvoicesenabled
                    @can('view crm invoices')
                        <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.invoices')) }}" icon="fas.file-invoice" link="{{ url(route('laravel-crm.invoices.index')) }}" />
                    @endcan
                @endhasinvoicesenabled

                @hasdeliveriesenabled
                    @can('view crm deliveries')
                        <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.deliveries')) }}" icon="fas.shipping-fast" link="{{ url(route('laravel-crm.deliveries.index')) }}" />
                    @endcan
                @endhasdeliveriesenabled

                @haspurchaseordersenabled
                    @can('view crm purchase orders')
                        <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.purchase_orders')) }}" icon="fas.file-invoice-dollar" link="{{ url(route('laravel-crm.purchase-orders.index')) }}" />
                    @endcan
                @endhaspurchaseordersenabled

                <hr class="my-2 border-t-[length:var(--border)] border-base-content/10">

                @can('view crm people')
                    <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.people')) }}" icon="fas.user-circle" link="{{ url(route('laravel-crm.people.index')) }}" />
                @endcan

                @can('view crm organizations')
                    <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.organizations')) }}" icon="fas.building" link="{{ url(route('laravel-crm.organizations.index')) }}" />
                @endcan

                @can('view crm users')
                    <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.users')) }}" icon="fas.user" link="{{ url(route('laravel-crm.users.index')) }}" />
                @endcan

                @hasteamsenabled
                    @can('view crm teams')
                        <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.teams')) }}" icon="fas.users" link="{{ url(route('laravel-crm.teams.index')) }}" />
                    @endcan
                @endhasteamsenabled
                
                <hr class="my-2 border-t-[length:var(--border)] border-base-content/10">
                @hasfeaturesenabled
                    @can('view crm features')
                    <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.features')) }}" icon="fas.lightbulb" link="{{ url(route('laravel-crm.features.index')) }}" />
                    @endcan
                @endhasfeaturesenabled

                @hasmonitoringenabled
                    @can('view crm monitors')
                        <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.monitors')) }}" icon="fas.heartbeat" link="{{ url(route('laravel-crm.monitors.index')) }}" />
                    @endcan
                @endhasmonitoringenabled
                
                @canany(['view crm features', 'view crm monitors'])
                    <hr class="my-2 border-t-[length:var(--border)] border-base-content/10">
                @endcanany
                
                @can('view crm products')
                    <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.products')) }}" icon="fas.tag" link="{{ url(route('laravel-crm.products.index')) }}" />
                @endcan

                @canany(['view crm settings', 'view crm roles', 'view crm pipelines', 'view crm product categories', 'view crm tax rates', 'view crm labels', 'view crm lead sources', 'view crm fields', 'view crm integrations', 'manage crm chat widgets'])
                    <x-mary-menu-separator />
                    <x-mary-menu-sub title="{{ ucfirst(__('laravel-crm::lang.settings')) }}" icon="fas.cog" link="###">
                        @can('view crm settings')
                            {{-- `exact` is required here: MaryUI marks an item active on URL
                                 prefix match, so /crm/settings would also light up for its
                                 nested pages (/crm/settings/templates, feature-statuses). --}}
                            <x-mary-menu-item exact link="{{ url(route('laravel-crm.settings.edit')) }}" title="{{ ucwords(__('laravel-crm::lang.general_settings')) }}" />
                            <x-mary-menu-item link="{{ url(route('laravel-crm.settings.templates.edit')) }}" title="{{ ucwords(__('laravel-crm::lang.templates')) }}" />
                        @endcan
                        @can('view crm roles')
                            <x-mary-menu-item link="{{ url(route('laravel-crm.roles.index')) }}" title="{{ new \Illuminate\Support\HtmlString(ucwords(__('laravel-crm::lang.roles_and_permissions'))) }}" />
                        @endcan
                        @can('view crm pipelines')
                            <x-mary-menu-item link="{{ url(route('laravel-crm.pipelines.index')) }}" title="{{ ucwords(__('laravel-crm::lang.pipelines')) }}" />
                            <x-mary-menu-item link="{{ url(route('laravel-crm.pipeline-stages.index')) }}" title="{{ ucwords(__('laravel-crm::lang.pipeline_stages')) }}" />
                        @endcan
                        @can('view crm product categories')
                            <x-mary-menu-item link="{{ url(route('laravel-crm.product-categories.index')) }}" title="{{ ucwords(__('laravel-crm::lang.product_categories')) }}" />
                        @endcan
                        @can('view crm tax rates')
                            <x-mary-menu-item link="{{ url(route('laravel-crm.tax-rates.index')) }}" title="{{ ucwords(__('laravel-crm::lang.tax_rates')) }}" />
                        @endcan
                        @can('view crm labels')
                            <x-mary-menu-item link="{{ url(route('laravel-crm.labels.index')) }}" title="{{ ucwords(__('laravel-crm::lang.labels')) }}" />
                        @endcan
                        @can('view crm lead sources')
                            <x-mary-menu-item link="{{ url(route('laravel-crm.lead-sources.index')) }}" title="{{ ucwords(__('laravel-crm::lang.lead_sources')) }}" />
                        @endcan
                        @can('view crm fields')
                            <x-mary-menu-item link="{{ url(route('laravel-crm.fields.index')) }}" title="{{ ucwords(__('laravel-crm::lang.custom_fields')) }}" />
                            <x-mary-menu-item link="{{ url(route('laravel-crm.field-groups.index')) }}" title="{{ ucwords(__('laravel-crm::lang.custom_field_groups')) }}" />
                        @endcan
                        @can('view crm integrations')
                            <x-mary-menu-item link="{{ url(route('laravel-crm.integrations.xero')) }}" title="{{ ucwords(__('laravel-crm::lang.integrations')) }}" />
                        @endcan
                        @haschatenabled
                            @can('manage crm chat widgets')
                                <x-mary-menu-item link="{{ url(route('laravel-crm.chat-widgets.index')) }}" title="{{ ucwords(__('laravel-crm::lang.chat_widgets')) }}" />
                            @endcan
                        @endhaschatenabled
                    </x-mary-menu-sub>
                @endcanany

                @if(config('laravel-crm.update_notifications'))
                    @can('view crm updates') 
                        <x-mary-menu-item title="{{ ucfirst(__('laravel-crm::lang.updates')) }}" icon="fas.cloud-download-alt" link="{{ url(route('laravel-crm.updates.index')) }}" />
                    @endcan
                @endif    

            </x-mary-menu>
        </x-slot:sidebar>
        <x-slot:content>
            @if(config('laravel-crm.update_notifications'))
                <livewire:crm-system-check />
            @endif
            <!-- Page Heading -->
            @if (isset($header))
                {{ $header }}
            @endif
            {{ $slot ?? null }}
        </x-slot:content>
    </x-mary-main>
    
    <x-mary-toast />

    {{-- Rendered once, outside every Livewire root, so an open preview
         survives wire:navigate visits and index-table re-renders. Opened from
         anywhere via the bubbling `crm-pdf-preview` window event. --}}
    <x-crm-pdf-preview />

    {{-- Likewise mounted once, outside every Livewire root, so the buttons on
         an index row all drive the same modal rather than one each. --}}
    <livewire:crm-get-link />

    @stack('modals')
    @livewireScripts
    @stack('livewire-js')
    </body>
</html>
