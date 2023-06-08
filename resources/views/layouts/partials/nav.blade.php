<div class="card mb-4">
    <div class="card-body py-3">
        <ul class="nav nav-pills nav-side flex-column">
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.dashboard') === 0) ? 'active' : '' }}" aria-current="dashboard" href="{{ url(route('laravel-crm.dashboard')) }}"><i class="fa fa-dashboard"></i> {{ ucfirst(__('laravel-crm::lang.dashboard')) }}</a></li>
            <li class="dropdown-divider"></li>
            @hasleadsenabled
                @can('view crm leads')
                <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.leads') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.leads.index')) }}"><i class="fa fa-crosshairs"></i> {{ ucfirst(__('laravel-crm::lang.leads')) }}</a></li>
                @endcan
            @endhasleadsenabled
            @hasdealsenabled
                @can('view crm deals')
                <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.deals') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.deals.index')) }}"><i class="fa fa-dollar"></i> {{ ucfirst(__('laravel-crm::lang.deals')) }}</a></li>
                @endcan
            @endhasdealsenabled
            @hasquotesenabled
                @can('view crm quotes')
                    <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.quotes') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.quotes.index')) }}"><i class="fa fa-file-text"></i> {{ ucfirst(__('laravel-crm::lang.quotes')) }}</a></li>
                @endcan
            @endhasquotesenabled
            @canany(['view crm activities', 'view crm tasks', 'view crm notes'])
                <li class="nav-item"><a class="nav-link {{ Str::contains(Route::currentRouteName(),[
                'laravel-crm.activities',
                'laravel-crm.notes',
                'laravel-crm.tasks',
                'laravel-crm.calls',
                'laravel-crm.meetings',
                'laravel-crm.lunches',
                'laravel-crm.files',
            ]) ? 'active' : '' }}" href="{{ url(route('laravel-crm.activities.index')) }}"><i class="fa fa-tasks"></i> {{ ucfirst(__('laravel-crm::lang.activity')) }}</a></li>
            @endcan   
            {{--@can('view crm tasks')
                <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.tasks') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.tasks.index')) }}">{{ ucfirst(__('laravel-crm::lang.tasks')) }}</a></li>
            @endcan
            @can('view crm notes')
                <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.notes') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.notes.index')) }}">{{ ucfirst(__('laravel-crm::lang.notes')) }}</a></li>
            @endcan--}}
            @canany(['view crm orders', 'view crm projects', 'view crm invoices', 'view crm deliveries'])
                <li class="dropdown-divider"></li>
            @endcan
            @hasordersenabled
                @can('view crm orders')
                    <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.orders') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.orders.index')) }}"><i class="fa fa-shopping-cart"></i> {{ ucfirst(__('laravel-crm::lang.orders')) }}</a></li>
                @endcan
            @endhasordersenabled
            @can('view crm projects')
                <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.projects') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.projects.index')) }}">{{ ucfirst(__('laravel-crm::lang.projects')) }}</a></li>
            @endcan
            @hasinvoicesenabled
                @can('view crm invoices')
                <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.invoices') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.invoices.index')) }}"><i class="fa fa-file-invoice"></i> {{ ucfirst(__('laravel-crm::lang.invoices')) }}</a></li>
                @endcan
            @endhasinvoicesenabled
            @hasdeliveriesenabled
                @can('view crm deliveries')
                    <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.deliveries') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.deliveries.index')) }}"><i class="fa fa-shipping-fast"></i> {{ ucfirst(__('laravel-crm::lang.deliveries')) }}</a></li>
                @endcan
            @endhasdeliveriesenabled
            @canany(['view crm clients', 'view crm people', 'view crm organisations'])
            <li class="dropdown-divider"></li>
            @endcan
            @can('view crm clients')
                <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.clients') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.clients.index')) }}"><i class="fa fa-address-card"></i> {{ ucfirst(__('laravel-crm::lang.clients')) }}</a></li>
            @endcan
            @can('view crm organisations')
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.organisations') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.organisations.index')) }}"><i class="fa fa-building"></i> {{ ucfirst(__('laravel-crm::lang.organizations')) }}</a></li>
            @endcan
            @can('view crm people')
                <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.people') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.people.index')) }}"><i class="fa fa-user-circle"></i> {{ ucfirst(__('laravel-crm::lang.people')) }}</a></li>
            @endcan
            @can('view crm users')
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.users') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.users.index')) }}"><i class="fa fa-user"></i> {{ ucfirst(__('laravel-crm::lang.users')) }}</a></li>
            @endcan
            @hasteamsenabled
            @can('view crm teams')
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.teams') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.teams.index')) }}"><i class="fa fa-users"></i> {{ ucfirst(__('laravel-crm::lang.teams')) }}</a></li>
            @endcan
            @endhasteamsenabled
            @canany(['view crm products'])
                <li class="dropdown-divider"></li>
            @endcan
            {{-- <li class="nav-item"><a class="nav-link" href="#">{{ ucfirst(__('laravel-crm::lang.email')) }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#">{{ ucfirst(__('laravel-crm::lang.documents')) }}</a></li>--}}
            @can('view crm products')
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.products') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.products.index')) }}"><i class="fa fa-tag"></i> {{ ucfirst(__('laravel-crm::lang.products')) }}</a></li>
            @endcan
            {{--<li class="nav-item"><a class="nav-link" href="#">{{ ucfirst(__('laravel-crm::lang.subscriptions')) }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#">{{ ucfirst(__('laravel-crm::lang.invoices')) }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#">{{ ucfirst(__('laravel-crm::lang.payments')) }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#">{{ ucfirst(__('laravel-crm::lang.reports')) }}</a></li>--}}
            @canany(['view crm settings', 'view crm updates'])
                <li class="dropdown-divider"></li>
            @endcan
            @can('view crm settings')
            <li class="nav-item"><a class="nav-link {{ Str::contains(Route::currentRouteName(),[
                'laravel-crm.settings',
                'laravel-crm.roles',
                'laravel-crm.product-categories',
                'laravel-crm.labels',
                'laravel-crm.fields',
                'laravel-crm.integrations',
            ]) ? 'active' : '' }}" href="{{ url(route('laravel-crm.settings.edit')) }}"><i class="fa fa-cog"></i> {{ ucfirst(__('laravel-crm::lang.settings')) }}</a></li>
            @endcan
            @can('view crm updates')
            <li class="nav-item"><a class="nav-link {{ Str::contains(Route::currentRouteName(),['laravel-crm.updates']) ? 'active' : '' }}" href="{{ url(route('laravel-crm.updates.index')) }}"><i class="fa fa-cloud-download"></i> {{ ucfirst(__('laravel-crm::lang.updates')) }}</a></li>
            @endcan
        </ul>
    </div>
</div>
