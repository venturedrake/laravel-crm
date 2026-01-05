<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.labels')) }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_label')) }}" link="{{ url(route('laravel-crm.labels.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
        </x-slot:actions>
    </x-mary-header>
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$labels" link="/labels/{id}" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('cell_color', $label)
                <span class="badge text-white" style="background-color: #{{ $label->hex }}; padding: 6px 8px;">
                    #{{ $label->hex }}
                </span>
            @endscope
            @scope('actions', $label)
                @can('view crm labels')
                    <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.labels.show', $label)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('edit crm labels')
                    <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.labels.edit', $label)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('delete crm labels')
                    <x-mary-button onclick="modalDeleteLabel{{ $label->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                    <x-crm-delete-confirm model="label" id="{{ $label->id }}"  />
                @endcan
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
