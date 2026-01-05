<div class="crm-content">
    <x-crm-header title="{{ ucfirst(__('laravel-crm::lang.label')) }}: {{ $label->name }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_labels')) }}" link="{{ url(route('laravel-crm.labels.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> | 
            @can('edit crm labels')
                <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.labels.edit', $label)) }}" class="btn-sm btn-square btn-outline" />
            @endcan
            @can('delete crm labels')
                <x-mary-button onclick="modalDeleteLabel{{ $label->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                <x-crm-delete-confirm model="label" id="{{ $label->id }}" />
            @endcan
        </x-slot:actions>
    </x-crm-header>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
        <div class="grid gap-y-3">
            <div class="flex flex-row gap-5">
                <strong>{{ ucfirst(__('laravel-crm::lang.name')) }}</strong>
                <span>
                    <span class="badge text-white" style="background-color: #{{ $label->hex }}; padding: 6px 8px;">
                        #{{ $label->hex }}
                    </span>
                </span>
            </div>
            <div class="flex flex-row gap-5">
                <strong>{{ ucfirst(__('laravel-crm::lang.description')) }}</strong>
                <span>
                     {{ $label->description }}
                </span>
            </div>
        </div>
    </x-mary-card>
</div>
