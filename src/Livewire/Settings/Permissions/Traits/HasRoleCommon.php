<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Permissions\Traits;

use Mary\Traits\Toast;

trait HasRoleCommon
{
    use Toast;

    public $name;

    public $description;

    public array $permissions = [];

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
        ];
    }
}
