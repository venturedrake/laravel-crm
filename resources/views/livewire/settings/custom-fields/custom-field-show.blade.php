<div class="crm-content">
    <x-crm-header title="{{ ucfirst(__('laravel-crm::lang.custom_field')) }}: {{ $field->name }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_custom_fields')) }}" link="{{ url(route('laravel-crm.fields.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> | 
            @can('edit crm fields')
                <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.fields.edit', $field)) }}" class="btn-sm btn-square btn-outline" />
            @endcan
            @can('delete crm fields')
                <x-mary-button onclick="modalDeleteField{{ $field->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                <x-crm-delete-confirm model="field" id="{{ $field->id }}" />
            @endcan
        </x-slot:actions>
    </x-crm-header>
    <div class="grid lg:grid-cols-2 gap-5">
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
                <div class="grid gap-y-3">
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.type')) }}</strong>
                        <span>
                           {{ ucwords(str_replace('_',' ',$field->type)) }}
                        </span>
                    </div>
                    @if(in_array($field->type, ['select', 'checkbox_multiple', 'radio']))
                        <div class="flex flex-row gap-5">
                            <strong>{{ ucfirst(__('laravel-crm::lang.options')) }}</strong>
                            <span>
                            @foreach($field->fieldOptions as $option)
                                {{ $option->label }}<br />
                            @endforeach
                            </span>
                        </div>
                    @endif
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.group')) }}</strong>
                        <span>
                           {{ $field->fieldGroup->name ?? null }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.required')) }}</strong>
                        <span>
                            {{ ($field->required == 1) ?  ucfirst(__('laravel-crm::lang.yes'))  : ucfirst(__('laravel-crm::lang.no')) }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.default')) }}</strong>
                        <span>
                            {{ $field->default }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.system')) }}</strong>
                        <span>
                            {{ ($field->system == 1) ?  ucfirst(__('laravel-crm::lang.yes'))  : ucfirst(__('laravel-crm::lang.no')) }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.handle')) }}</strong>
                        <span>
                           {{ $field->handle }}
                        </span>
                    </div>
                </div>
            </x-mary-card>
        </div>
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.attached_to')) }}" shadow separator>
                @foreach(\VentureDrake\LaravelCrm\Models\FieldModel::where('field_id', $field->id)->get() as $fieldModel)
                    <span class="badge badge-neutral">{{ \Illuminate\Support\Str::plural(class_basename($fieldModel->model)) }}</span>
                @endforeach
            </x-mary-card>
        </div>
    </div>
    
</div>
