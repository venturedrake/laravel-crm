<div class="card">
    <div class="card-body py-3">
        <ul class="nav nav-pills flex-column">
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.dashboard') === 0) ? 'active' : '' }}" aria-current="dashboard" href="{{ url(route('laravel-crm.dashboard')) }}">{{ ucfirst(__('laravel-crm::lang.dashboard')) }}</a></li>
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.leads') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.leads.index')) }}">{{ ucfirst(__('laravel-crm::lang.leads')) }}</a></li>
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.deals') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.deals.index')) }}">{{ ucfirst(__('laravel-crm::lang.deals')) }}</a></li>
            <li class="dropdown-divider"></li>
            {{--<li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.activities') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.activities.index')) }}">{{ ucfirst(__('laravel-crm::lang.activities')) }}</a></li>--}}
            {{--<li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.contacts') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.contacts.index')) }}">{{ ucfirst(__('laravel-crm::lang.contacts')) }}</a></li>--}}
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.people') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.people.index')) }}">{{ ucfirst(__('laravel-crm::lang.people')) }}</a></li>
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.organisations') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.organisations.index')) }}">{{ ucfirst(__('laravel-crm::lang.organizations')) }}</a></li>
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.users') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.users.index')) }}">{{ ucfirst(__('laravel-crm::lang.users')) }}</a></li>
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.teams') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.teams.index')) }}">{{ ucfirst(__('laravel-crm::lang.teams')) }}</a></li>
            <li class="dropdown-divider"></li>
           {{-- <li class="nav-item"><a class="nav-link" href="#">{{ ucfirst(__('laravel-crm::lang.email')) }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#">{{ ucfirst(__('laravel-crm::lang.documents')) }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#">{{ ucfirst(__('laravel-crm::lang.projects')) }}</a></li>--}}
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.products') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.products.index')) }}">{{ ucfirst(__('laravel-crm::lang.products')) }}</a></li>
            {{--<li class="nav-item"><a class="nav-link" href="#">{{ ucfirst(__('laravel-crm::lang.subscriptions')) }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#">{{ ucfirst(__('laravel-crm::lang.invoices')) }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#">{{ ucfirst(__('laravel-crm::lang.payments')) }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#">{{ ucfirst(__('laravel-crm::lang.reports')) }}</a></li>--}}
            <li class="dropdown-divider"></li>
            <li class="nav-item"><a class="nav-link {{ Str::contains(Route::currentRouteName(),['laravel-crm.settings','laravel-crm.roles']) ? 'active' : '' }}" href="{{ url(route('laravel-crm.settings.edit')) }}">{{ ucfirst(__('laravel-crm::lang.settings')) }}</a></li>
            <li class="nav-item"><a class="nav-link {{ Str::contains(Route::currentRouteName(),['laravel-crm.updates']) ? 'active' : '' }}" href="{{ url(route('laravel-crm.updates.index')) }}">{{ ucfirst(__('laravel-crm::lang.updates')) }}</a></li>
        </ul>
    </div>
</div>