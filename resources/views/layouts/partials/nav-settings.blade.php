<ul class="nav nav-tabs card-header-tabs" id="bologna-list" role="tablist">
    @can('view crm settings')
    <li class="nav-item">
        <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.settings') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.settings.edit')) }}" role="tab" aria-controls="settings" aria-selected="true">{{ ucwords(__('laravel-crm::lang.general_settings')) }}</a>
    </li>
    @endcan
    @can('view crm roles')
        <li class="nav-item">
            <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.roles') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.roles.index')) }}" role="tab" aria-controls="roles" aria-selected="false">{{ ucwords(__('laravel-crm::lang.roles_and_permissions')) }}</a>
        </li>
    @endcan
    @can('view crm product categories')
    <li class="nav-item">
        <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.product-categories') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.product-categories.index')) }}" role="product-categories" aria-controls="product-categories" aria-selected="false">{{ ucwords(__('laravel-crm::lang.product_categories')) }}</a>
    </li>
    @endcan
    @can('view crm product attributes')
        <li class="nav-item">
            <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.product-attributes') === 0) ? 'active' : '' }}" href="#" role="product-attributes" aria-controls="product-attributes" aria-selected="false">{{ ucwords(__('laravel-crm::lang.product_attributes')) }}</a>
        </li>
    @endcan
    @can('view crm labels')
        <li class="nav-item">
            <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.labels') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.labels.index')) }}" role="tab" aria-controls="labels" aria-selected="false">{{ ucwords(__('laravel-crm::lang.labels')) }}</a>
        </li>
    @endcan
    @can('view crm fields')
       <li class="nav-item">
            <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.fields') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.fields.index')) }}" role="tab" aria-controls="fields" aria-selected="false">{{ ucwords(__('laravel-crm::lang.custom_fields')) }}</a>
       </li>
        <li class="nav-item">
            <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.field-groups') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.field-groups.index')) }}" role="tab" aria-controls="field-groups" aria-selected="false">{{ ucwords(__('laravel-crm::lang.custom_field_groups')) }}</a>
        </li>
    @endcan
    @can('view crm integrations')
    <li class="nav-item">
        <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.integrations.xero') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.integrations.xero')) }}" role="tab" aria-controls="integrations" aria-selected="false">{{ ucwords(__('laravel-crm::lang.integrations')) }}</a>
    </li>
    @endcan
</ul>