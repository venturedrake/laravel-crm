<?php

namespace VentureDrake\LaravelCrm\Livewire\EmailTemplates;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\EmailTemplate;

class EmailTemplateShow extends Component
{
    public EmailTemplate $template;

    public function mount(EmailTemplate $template): void
    {
        $this->template = $template;
    }

    public function render()
    {
        return view('laravel-crm::livewire.email-templates.email-template-show');
    }
}
