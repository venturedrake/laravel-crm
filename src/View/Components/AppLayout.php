<?php

namespace VentureDrake\LaravelCrm\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    public function __construct(public string $title = '') {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('laravel-crm::layouts.app');
    }
}
