<div class="card">
    <div class="card-body">
        <ul class="nav nav-pills flex-column">
            {{--<li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.dashboard') === 0) ? 'active' : '' }}" aria-current="dashboard" href="{{ url(route('laravel-crm.dashboard')) }}">Dashboard</a></li>--}}
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.leads') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.leads.index')) }}">Leads</a></li>
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.deals') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.deals.index')) }}">Deals</a></li>
            <li class="dropdown-divider"></li>
            {{--<li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.activities') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.activities.index')) }}">Activities</a></li>--}}
            {{--<li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.contacts') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.contacts.index')) }}">Contacts</a></li>--}}
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.people') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.people.index')) }}">People</a></li>
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.organisations') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.organisations.index')) }}">Organisations</a></li>
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.users') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.users.index')) }}">Users</a></li>
            <li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.teams') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.teams.index')) }}">Teams</a></li>
            {{--<li class="dropdown-divider"></li>--}}
           {{-- <li class="nav-item"><a class="nav-link" href="#">Email</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Documents</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Projects</a></li>--}}
            {{--<li class="nav-item"><a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.products') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.products.index')) }}">Products</a></li>--}}
            {{--<li class="nav-item"><a class="nav-link" href="#">Subscriptions</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Invoices</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Payments</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Reports</a></li>--}}
        </ul>
    </div>
</div>