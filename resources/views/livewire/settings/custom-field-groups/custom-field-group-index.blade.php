<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.custom_field_groups')) }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_custom_field_group')) }}" link="{{ url(route('laravel-crm.field-groups.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
        </x-slot:actions>
    </x-mary-header>
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$fieldGroups" link="/field-groups/{id}" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('actions', $fieldGroup)
                @can('view crm fields')
                    <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.field-groups.show', $fieldGroup)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('edit crm fields')
                    <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.field-groups.edit', $fieldGroup)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('delete crm fields')
                    <x-mary-button onclick="modalDeleteFieldGroup{{ $fieldGroup->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                    <x-crm-delete-confirm model="fieldGroup" id="{{ $fieldGroup->id }}" deleting="custom field group"  />
                @endcan
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
