@props([
    'model',
    'group' => false,
])
@php
    if($model->id) {
        $fields = $model->fields;
    } else {
        $fields = \VentureDrake\LaravelCrm\Models\FieldModel::where('model', get_class($model))->get();
    }

    if ($group) {
        $ungrouped = $fields->filter(fn($f) => !$f->field || !$f->field->field_group_id);
        $grouped   = $fields->filter(fn($f) => $f->field && $f->field->field_group_id)
                            ->groupBy(fn($f) => $f->field->field_group_id);
    }
@endphp

@if($group)
    {{-- Ungrouped fields --}}
    @foreach($ungrouped as $fieldValueOrModel)
        @if($fieldValueOrModel->field)
            @php
                $field = $fieldValueOrModel->field;
                $key   = 'fields.'.$field->id;
                $label = ucfirst(__($field->name));
            @endphp
            @include('laravel-crm::components.custom-fields._field', compact('field', 'key', 'label'))
        @endif
    @endforeach

    {{-- Grouped fields --}}
    @foreach($grouped as $groupId => $groupFields)
        @php
            $groupName = $groupFields->first()->field->fieldGroup->name ?? '';
        @endphp
        <x-mary-card title="{{ $groupName }}" class="mt-5" separator>
            <div class="grid gap-3" wire:key="custom-field-group-{{ $groupId }}">
                @foreach($groupFields as $fieldValueOrModel)
                    @if($fieldValueOrModel->field)
                        @php
                            $field = $fieldValueOrModel->field;
                            $key   = 'fields.'.$field->id;
                            $label = ucfirst(__($field->name));
                        @endphp
                        @include('laravel-crm::components.custom-fields._field', compact('field', 'key', 'label'))
                    @endif
                @endforeach
            </div>
        </x-mary-card>
    @endforeach
@else
    {{-- Flat list — ungrouped fields only --}}
    @foreach($fields->filter(fn($f) => $f->field && !$f->field->field_group_id) as $fieldValueOrModel)
        @php
            $field = $fieldValueOrModel->field;
            $key   = 'fields.'.$field->id;
            $label = ucfirst(__($field->name));
        @endphp
        @include('laravel-crm::components.custom-fields._field', compact('field', 'key', 'label'))
    @endforeach
@endif

