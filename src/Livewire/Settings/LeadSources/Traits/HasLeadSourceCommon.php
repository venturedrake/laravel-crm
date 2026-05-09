<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\LeadSources\Traits;

use Mary\Traits\Toast;

trait HasLeadSourceCommon
{
    use Toast;

    public $name;

    public $description;

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
            'description' => 'nullable|max:1000',
        ];
    }
}
