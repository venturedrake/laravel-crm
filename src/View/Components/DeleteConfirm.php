<?php

namespace VentureDrake\LaravelCrm\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DeleteConfirm extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $model,
        public string $id,
        // Shown above the "cannot be reversed" line, e.g. the records still
        // linked to a contact about to be deleted.
        public ?string $warning = null,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('laravel-crm::components.delete-confirm');
    }
}
