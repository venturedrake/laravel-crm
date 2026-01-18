<?php

namespace VentureDrake\LaravelCrm\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Addresses extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public array $addresses,
        public array $addressTypes,
        public array $countries,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('laravel-crm::components.addresses');
    }
}
