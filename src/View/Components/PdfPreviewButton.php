<?php

namespace VentureDrake\LaravelCrm\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * The eye button that opens the PDF preview slide-over.
 *
 * Deliberately stateless: it only fires a window event, which the single
 * <x-crm-pdf-preview /> instance in the layout listens for. That is what lets
 * it sit inside a Livewire index row without giving every row its own drawer.
 */
class PdfPreviewButton extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $url,
        public string $downloadUrl,
        public ?string $title = null,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('laravel-crm::components.pdf-preview-button');
    }
}
