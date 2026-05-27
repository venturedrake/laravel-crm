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
            'color' => 'nullable|max:32',
            'order' => 'nullable|integer',
            'is_default' => 'boolean',
            'is_closed' => 'boolean',
        ];
    }
}
