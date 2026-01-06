<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\ProductCategories\Traits;

use Mary\Traits\Toast;

trait HasProductCategoryCommon
{
    use Toast;

    public $name;

    public $description;

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
        ];
    }
}
