<?php

namespace VentureDrake\LaravelCrm\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

/**
 * A link to a related record that may since have been soft-deleted.
 *
 * Renders nothing without a record, the label with a "deleted" badge when the
 * record is trashed (its show route would 404), and a link otherwise.
 */
class RecordLink extends Component
{
    public function __construct(
        public ?Model $record,
        public string $route,
        public ?string $label = null,
        public ?string $class = null,
    ) {}

    public function trashed(): bool
    {
        return $this->record !== null
            && method_exists($this->record, 'trashed')
            && $this->record->trashed();
    }

    public function shouldRender(): bool
    {
        return $this->record !== null;
    }

    public function render(): View|Closure|string
    {
        return view('laravel-crm::components.record-link');
    }
}
