<?php

namespace VentureDrake\LaravelCrm\Livewire\Traits;

use Livewire\Attributes\Locked;

trait GeneratesContactTitle
{
    #[Locked]
    public ?string $generatedTitle = null;

    protected function updateGeneratedTitle($value, string $type): void
    {
        if (filled($this->title) && $this->title !== $this->generatedTitle) {
            return;
        }

        $name = trim((string) ($value ?: $this->person_name));
        $this->title = $this->generatedTitle = $name === ''
            ? ''
            : $name.' '.ucfirst(trans('laravel-crm::lang.'.$type));
    }
}
