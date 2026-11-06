<?php

namespace VentureDrake\LaravelCrm\Livewire\Traits;

/**
 * The phones and emails a form is editing as saved, which the contact rules let
 * through unchanged. Create forms have none; edit forms override these.
 */
trait HasSavedContactDetails
{
    protected function savedPhones(): array
    {
        return [];
    }

    protected function savedEmails(): array
    {
        return [];
    }
}
