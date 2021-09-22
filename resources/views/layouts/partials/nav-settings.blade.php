<ul class="nav nav-tabs card-header-tabs" id="bologna-list" role="tablist">
    @can('view crm settings')
    <li class="nav-item">
        <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.settings') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.settings.edit')) }}" role="tab" aria-controls="settings" aria-selected="true">{{ ucwords(__('laravel-crm::lang.general_settings')) }}</a>
    </li>
    @endcan
    @can('view crm roles')
        <li class="nav-item">
            <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.roles') === 0) ? 'active' : '' }}"  href="{{ url(route('laravel-crm.roles.index')) }}" role="tab" aria-controls="roles" aria-selected="false">{{ ucwords(__('laravel-crm::lang.roles_and_permissions')) }}</a>
        </li>
    @endcan
    @can('view crm product categories')
    <li class="nav-item">
        <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.product-categories') === 0) ? 'active' : '' }}"  href="{{ url(route('laravel-crm.product-categories.index')) }}" role="tab" aria-controls="product-categories" aria-selected="false">{{ ucwords(__('laravel-crm::lang.product_categories')) }}</a>
    </li>
    @endcan
    @can('view crm labels')
        <li class="nav-item">
            <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.labels') === 0) ? 'active' : '' }}"  href="{{ url(route('laravel-crm.labels.index')) }}" role="tab" aria-controls="roles" aria-selected="false">{{ ucwords(__('laravel-crm::lang.labels')) }}</a>
        </li>
    @endcan
    {{--<li class="nav-item">
        <a class="nav-link" href="#integrations" role="tab" aria-controls="integrations" aria-selected="false">{{ ucwords(__('laravel-crm::lang.integrations')) }}</a>
    </li>--}}
</ul>