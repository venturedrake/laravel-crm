<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.custom_fields')) }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_custom_field')) }}" link="{{ url(route('laravel-crm.fields.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
        </x-slot:actions>
    </x-mary-header>
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$fields" link="/fields/{id}" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('cell_attached_to', $field)
                @foreach(\VentureDrake\LaravelCrm\Models\FieldModel::where('field_id', $field->id)->get() as $fieldModel)
                    <span class="badge badge-neutral badge-sm">
                        {{ \Illuminate\Support\Str::plural(class_basename($fieldModel->model)) }}
                    </span>
                @endforeach
            @endscope
            @scope('actions', $field)
                @can('view crm fields')
                    <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.fields.show', $field)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('edit crm fields')
                    <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.fields.edit', $field)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('delete crm fields')
                    <x-mary-button onclick="modalDeleteField{{ $field->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                    <x-crm-delete-confirm model="field" id="{{ $field->id }}" />
                @endcan
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
