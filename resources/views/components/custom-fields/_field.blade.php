@switch($field->type)
    @case('text')
        <x-mary-input wire:model="{{ $key }}" label="{{ $label }}" />
        @break

    @case('textarea')
        <x-mary-textarea wire:model="{{ $key }}" label="{{ $label }}" rows="5" />
        @break

    @case('select')
        <x-mary-select
            wire:model="{{ $key }}"
            label="{{ $label }}"
            placeholder="-"
            :options="$field->fieldOptions"
            option-value="id"
            option-label="label"
        />
        @break

    @case('checkbox')
        <x-mary-checkbox wire:model="{{ $key }}" label="{{ $label }}" />
        @break

    @case('checkbox_multiple')
        <x-mary-choices
            wire:model="{{ $key }}"
            label="{{ $label }}"
            :options="$field->fieldOptions"
            option-value="id"
            option-label="label"
            searchable
        />
        @break

    @case('radio')
        <x-mary-radio
            wire:model="{{ $key }}"
            label="{{ $label }}"
            :options="$field->fieldOptions"
            option-value="id"
            option-label="label"
        />
        @break

    @case('date')
        <x-mary-datepicker wire:model="{{ $key }}" label="{{ $label }}" icon="fas.calendar" />
        @break
@endswitch

