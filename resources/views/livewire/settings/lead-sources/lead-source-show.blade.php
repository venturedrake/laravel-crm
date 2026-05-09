<div class="crm-content">
    <x-crm-header title="{{ ucfirst(__('laravel-crm::lang.lead_source')) }}: {{ $leadSource->name }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_lead_sources')) }}" link="{{ url(route('laravel-crm.lead-sources.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> |
            @can('edit crm lead sources')
                <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.lead-sources.edit', $leadSource)) }}" class="btn-sm btn-square btn-outline" />
            @endcan
            @can('delete crm lead sources')
                <x-mary-button onclick="modalDeleteLeadSource{{ $leadSource->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                <x-crm-delete-confirm model="leadSource" id="{{ $leadSource->id }}" />
            @endcan
        </x-slot:actions>
    </x-crm-header>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
        <div class="grid gap-y-3">
            <div class="flex flex-row gap-5">
                <strong>{{ ucfirst(__('laravel-crm::lang.name')) }}</strong>
                <span>{{ $leadSource->name }}</span>
            </div>
            <div class="flex flex-row gap-5">
                <strong>{{ ucfirst(__('laravel-crm::lang.description')) }}</strong>
                <span>{{ $leadSource->description }}</span>
            </div>
            <div class="flex flex-row gap-5">
                <strong>{{ ucfirst(__('laravel-crm::lang.leads')) }}</strong>
                <span>{{ $leadSource->leads()->count() }}</span>
            </div>
        </div>
    </x-mary-card>
</div>

