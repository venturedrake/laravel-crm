<?php

namespace VentureDrake\LaravelCrm\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * The PDF preview slide-over itself.
 *
 * Rendered exactly once, in the app layout beside <x-mary-toast />, so it sits
 * outside every Livewire root — a wire:navigate visit or a table re-render
 * can't tear the open panel down mid-view.
 */
class PdfPreview extends Component
{
    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('laravel-crm::components.pdf-preview');
    }
}
