<ul class="nav nav-tabs card-header-tabs" id="bologna-list" role="tablist">
    <li class="nav-item">
        <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.settings') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.settings.edit')) }}" role="tab" aria-controls="settings" aria-selected="true">General Settings</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.product-categories') === 0) ? 'active' : '' }}"  href="{{ url(route('laravel-crm.product-categories.index')) }}" role="tab" aria-controls="product-categories" aria-selected="false">Product Categories</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.roles') === 0) ? 'active' : '' }}"  href="{{ url(route('laravel-crm.roles.index')) }}" role="tab" aria-controls="roles" aria-selected="false">Roles & Permissions</a>
    </li>
    {{--<li class="nav-item">
        <a class="nav-link" href="#integrations" role="tab" aria-controls="integrations" aria-selected="false">Integrations</a>
    </li>--}}
</ul>