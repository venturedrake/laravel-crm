<?php

namespace VentureDrake\LaravelCrm\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Phones extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public array $phones,
        public array $phoneTypes,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('laravel-crm::components.phones');
    }
}
