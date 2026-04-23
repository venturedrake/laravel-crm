@props([
    'model',
    'group' => false,
])
@php
    $fieldValues = collect();

    if ($model && $model->id) {
        $fieldValues = $model->fields->filter(fn($fv) => $fv->field);
    }

    if ($group) {
        $ungrouped = $fieldValues->filter(fn($fv) => ! $fv->field->field_group_id);
        $grouped   = $fieldValues->filter(fn($fv) => $fv->field->field_group_id)
                                 ->groupBy(fn($fv) => $fv->field->field_group_id);
    } else {
        $ungrouped = $fieldValues->filter(fn($fv) => ! $fv->field->field_group_id);
        $grouped   = collect();
    }
@endphp

@if($ungrouped->isNotEmpty() || $grouped->isNotEmpty())
    @if($ungrouped->isNotEmpty())
        <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.custom_fields')) }}" shadow separator>
            <div class="grid gap-y-3">
                @foreach($ungrouped as $fieldValue)
                    @include('laravel-crm::components.custom-field-values._row', ['fieldValue' => $fieldValue])
                @endforeach
            </div>
        </x-mary-card>
    @endif

    @foreach($grouped as $groupId => $groupValues)
        @php
            $groupName = $groupValues->first()->field->fieldGroup->name ?? '';
        @endphp
        <x-mary-card title="{{ $groupName }}" shadow separator>
            <div class="grid gap-y-3">
                @foreach($groupValues as $fieldValue)
                    @include('laravel-crm::components.custom-field-values._row', ['fieldValue' => $fieldValue])
                @endforeach
            </div>
        </x-mary-card>
    @endforeach
@endif


