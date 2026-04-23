@php
    $field = $fieldValue->field;
    $label = ucfirst(__($field->name));
    $raw   = $fieldValue->value;

    $display = null;

    switch ($field->type) {
        case 'checkbox':
            $display = ((bool) $raw)
                ? ucfirst(__('laravel-crm::lang.yes'))
                : ucfirst(__('laravel-crm::lang.no'));
            break;

        case 'select':
        case 'radio':
            $option = $field->fieldOptions->firstWhere('id', $raw);
            $display = $option?->label;
            break;

        case 'checkbox_multiple':
            $values = is_string($raw) ? json_decode($raw, true) : $raw;
            $values = is_array($values) ? $values : [];
            $display = $field->fieldOptions
                ->whereIn('id', $values)
                ->pluck('label')
                ->implode(', ');
            break;

        case 'date':
            $display = $raw ? \Carbon\Carbon::parse($raw)->format('Y-m-d') : null;
            break;

        case 'textarea':
        case 'text':
        default:
            $display = $raw;
            break;
    }
@endphp

<div class="flex flex-row gap-5">
    <strong>{{ $label }}</strong>
    <span>{{ $display !== null && $display !== '' ? $display : '—' }}</span>
</div>

