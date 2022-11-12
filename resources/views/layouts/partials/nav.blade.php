<div class="card mb-4">
    <div class="card-body py-3">
        <ul class="nav nav-pills flex-column">
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.dashboard') === 0) ? 'active' : '' }}" aria-current="dashboard" href="{{ url(route('laravel-crm.dashboard')) }}">{{ ucfirst(__('laravel-crm::lang.dashboard')) }}</a></li>
            <li class="dropdown-divider"></li>
            @can('view crm leads')
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.leads') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.leads.index')) }}">{{ ucfirst(__('laravel-crm::lang.leads')) }}</a></li>
            @endcan
            @can('view crm deals')
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.deals') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.deals.index')) }}">{{ ucfirst(__('laravel-crm::lang.deals')) }}</a></li>
            @endcan
            @can('view crm quotes')
                <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.quotes') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.quotes.index')) }}">{{ ucfirst(__('laravel-crm::lang.quotes')) }}</a></li>
            @endcan
            @can('view crm tasks')
                <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.tasks') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.tasks.index')) }}">{{ ucfirst(__('laravel-crm::lang.tasks')) }}</a></li>
            @endcan
            {{--@canany(['view crm orders', 'view crm projects'])
                <li class="dropdown-divider"></li>
            @endcan
            @can('view crm orders')
                <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.orders') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.orders.index')) }}">{{ ucfirst(__('laravel-crm::lang.orders')) }}</a></li>
            @endcan
            @can('view crm projects')
                <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.projects') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.projects.index')) }}">{{ ucfirst(__('laravel-crm::lang.projects')) }}</a></li>
            @endcan--}}
            @canany(['view crm people', 'view crm organisations'])
            <li class="dropdown-divider"></li>
            @endcan
            {{--<li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.activities') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.activities.index')) }}">{{ ucfirst(__('laravel-crm::lang.activities')) }}</a></li>--}}
            {{--<li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.contacts') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.contacts.index')) }}">{{ ucfirst(__('laravel-crm::lang.contacts')) }}</a></li>--}}
            @can('view crm people')
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.people') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.people.index')) }}">{{ ucfirst(__('laravel-crm::lang.people')) }}</a></li>
            @endcan
            @can('view crm organisations')
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.organisations') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.organisations.index')) }}">{{ ucfirst(__('laravel-crm::lang.organizations')) }}</a></li>
            @endcan
            @can('view crm users')
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.users') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.users.index')) }}">{{ ucfirst(__('laravel-crm::lang.users')) }}</a></li>
            @endcan
            @can('view crm teams')
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.teams') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.teams.index')) }}">{{ ucfirst(__('laravel-crm::lang.teams')) }}</a></li>
            @endcan
            @canany(['view crm products'])
                <li class="dropdown-divider"></li>
            @endcan
            {{-- <li class="nav-item"><a class="nav-link" href="#">{{ ucfirst(__('laravel-crm::lang.email')) }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#">{{ ucfirst(__('laravel-crm::lang.documents')) }}</a></li>--}}
            @can('view crm products')
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.products') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.products.index')) }}">{{ ucfirst(__('laravel-crm::lang.products')) }}</a></li>
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
            ]) ? 'active' : '' }}" href="{{ url(route('laravel-crm.settings.edit')) }}">{{ ucfirst(__('laravel-crm::lang.settings')) }}</a></li>
            @endcan
            @can('view crm updates')
            <li class="nav-item"><a class="nav-link {{ Str::contains(Route::currentRouteName(),['laravel-crm.updates']) ? 'active' : '' }}" href="{{ url(route('laravel-crm.updates.index')) }}">{{ ucfirst(__('laravel-crm::lang.updates')) }}</a></li>
            @endcan
        </ul>
    </div>
</div>