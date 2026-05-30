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
        <div>
            <fieldset class="fieldset py-0">
                <legend class="fieldset-legend mb-2">{{ $label }}</legend>
                <div class="gap-1 grid [&_fieldset]:py-0">
                    @foreach($field->fieldOptions as $option)
                        <x-mary-checkbox
                            wire:model="{{ $key }}"
                            value="{{ $option->id }}"
                            label="{{ $option->label }}"
                        />
                    @endforeach
                </div>
            </fieldset>
        </div>
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

