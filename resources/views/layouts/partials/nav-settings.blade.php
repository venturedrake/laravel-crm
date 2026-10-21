<x-mary-card shadow>
    <x-mary-menu activate-by-route class="p-0">
        @can('view crm settings')
            {{-- `exact` is required here: MaryUI marks an item active on URL
                 prefix match, so /crm/settings would also light up for its
                 nested pages (/crm/settings/templates, feature-statuses). --}}
            <x-mary-menu-item exact link="{{ url(route('laravel-crm.settings.edit')) }}" title="{{ ucwords(__('laravel-crm::lang.general_settings')) }}"  />
            <x-mary-menu-item link="{{ url(route('laravel-crm.settings.templates.edit')) }}" title="{{ ucwords(__('laravel-crm::lang.templates')) }}"  />
        @endcan
        @can('view crm roles')
            <x-mary-menu-item link="{{ url(route('laravel-crm.roles.index')) }}" title="{{ new \Illuminate\Support\HtmlString(ucwords(__('laravel-crm::lang.roles_and_permissions'))) }}"  />
        @endcan
        @can('view crm pipelines')
            <x-mary-menu-item link="{{ url(route('laravel-crm.pipelines.index')) }}" title="{{ ucwords(__('laravel-crm::lang.pipelines')) }}"  />
            <x-mary-menu-item link="{{ url(route('laravel-crm.pipeline-stages.index')) }}" title="{{ ucwords(__('laravel-crm::lang.pipeline_stages')) }}"  />
        @endcan
        @can('view crm product categories')
            <x-mary-menu-item link="{{ url(route('laravel-crm.product-categories.index')) }}" title="{{ ucwords(__('laravel-crm::lang.product_categories')) }}"  />
        @endcan
        @can('view crm tax rates')
            <x-mary-menu-item link="{{ url(route('laravel-crm.tax-rates.index')) }}" title="{{ ucwords(__('laravel-crm::lang.tax_rates')) }}"  />
        @endcan
        @can('view crm labels')
            <x-mary-menu-item link="{{ url(route('laravel-crm.labels.index')) }}" title="{{ ucwords(__('laravel-crm::lang.labels')) }}"  />
        @endcan
        @can('view crm lead sources')
            <x-mary-menu-item link="{{ url(route('laravel-crm.lead-sources.index')) }}" title="{{ ucwords(__('laravel-crm::lang.lead_sources')) }}"  />
        @endcan
        @can('view crm fields')
            <x-mary-menu-item link="{{ url(route('laravel-crm.fields.index')) }}" title="{{ ucwords(__('laravel-crm::lang.custom_fields')) }}"  />
            <x-mary-menu-item link="{{ url(route('laravel-crm.field-groups.index')) }}" title="{{ ucwords(__('laravel-crm::lang.custom_field_groups')) }}"  />
        @endcan
    </x-mary-menu>
</x-mary-card>
