<div class="crm-content">
    <x-crm-header title="{{ ucfirst(__('laravel-crm::lang.custom_field_group')) }}: {{ $fieldGroup->name }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_custom_field_groups')) }}" link="{{ url(route('laravel-crm.field-groups.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> | 
            @can('edit crm fields')
                <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.field-groups.edit', $fieldGroup)) }}" class="btn-sm btn-square btn-outline" />
            @endcan
            @can('delete crm fields')
                <x-mary-button onclick="modalDeleteFieldGroup{{ $fieldGroup->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                <x-crm-delete-confirm model="fieldGroup" id="{{ $fieldGroup->id }}" deleting="custom field group" />
            @endcan
        </x-slot:actions>
    </x-crm-header>
    <div class="grid lg:grid-cols-2 gap-5">
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
                <div class="grid gap-y-3">
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.system')) }}</strong>
                        <span>
                            {{ ($fieldGroup->system == 1) ?  ucfirst(__('laravel-crm::lang.yes'))  : ucfirst(__('laravel-crm::lang.no')) }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.handle')) }}</strong>
                        <span>
                           {{ $fieldGroup->handle }}
                        </span>
                    </div>
                </div>
            </x-mary-card>
        </div>
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.fields')) }}" shadow separator>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                        <tr>
                            <th class="px-0 pt-0">{{ ucfirst(__('laravel-crm::lang.type')) }}</th>
                            <th class="px-0 pt-0">{{ ucfirst(__('laravel-crm::lang.group')) }}</th>
                            <th class="px-0 pt-0">{{ ucfirst(__('laravel-crm::lang.name')) }}</th>
                            <th class="px-0 pt-0">{{ ucfirst(__('laravel-crm::lang.required')) }}</th>
                            <th class="px-0 pt-0">{{ ucfirst(__('laravel-crm::lang.default')) }}</th>
                            <th class="px-0 pt-0">{{ ucfirst(__('laravel-crm::lang.system')) }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($fieldGroup->fields as $field)
                            <tr class="has-link" data-url="{{ url(route('laravel-crm.fields.show',$field)) }}">
                                <td>{{ ucwords(str_replace('_',' ',$field->type)) }}</td>
                                <td>{{ $field->fieldGroup->name ?? null }}</td>
                                <td>{{ $field->name }}</td>
                                <td>{{ ($field->required == 1) ?  ucfirst(__('laravel-crm::lang.yes'))  : ucfirst(__('laravel-crm::lang.no')) }}</td>
                                <td>{{ $field->default }}</td>
                                <td>{{ ($field->system == 1) ?  ucfirst(__('laravel-crm::lang.yes'))  : ucfirst(__('laravel-crm::lang.no')) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-mary-card>
        </div>
    </div>
    
</div>
