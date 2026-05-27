<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Features\Traits;

use Mary\Traits\Toast;

trait HasFeatureStatusCommon
{
    use Toast;

    public ?string $name = null;

    public ?string $description = null;

    public ?string $color = '#6c757d';

    public ?int $order = null;

    public bool $is_default = false;

    public bool $is_closed = false;

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
            'description' => 'nullable|max:1000',
            'color' => ['nullable', 'string', 'regex:/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/'],
            'order' => 'nullable|integer',
            'is_default' => 'boolean',
            'is_closed' => 'boolean',
        ];
    }
}
