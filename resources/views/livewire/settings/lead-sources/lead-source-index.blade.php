<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.lead_sources')) }}" progress-indicator>
        <x-slot:actions>
            @can('create crm lead sources')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_lead_source')) }}" link="{{ url(route('laravel-crm.lead-sources.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
            @endcan
        </x-slot:actions>
    </x-mary-header>
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$leadSources" :link="route('laravel-crm.lead-sources.show', ['leadSource' => '[id]'])" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('actions', $leadSource)
                @can('view crm lead sources')
                    <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.lead-sources.show', $leadSource)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('edit crm lead sources')
                    <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.lead-sources.edit', $leadSource)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('delete crm lead sources')
                    <x-mary-button onclick="modalDeleteLeadSource{{ $leadSource->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                    <x-crm-delete-confirm model="leadSource" id="{{ $leadSource->id }}" />
                @endcan
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>

