<?php

namespace VentureDrake\LaravelCrm\Livewire\Users;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Users\Traits\HasUserCommon;

class UserCreate extends Component
{
    use HasUserCommon;

    public $layout = 'full';

    public function mount()
    {
        $this->mountCommon();
    }

    public function save()
    {
        $this->validate();

        // Create a request object to pass to services
        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        //
    }

    public function render()
    {
        return view('laravel-crm::livewire.users.user-create');
    }
}
