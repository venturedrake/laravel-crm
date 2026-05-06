<?php

namespace VentureDrake\LaravelCrm\Livewire\SmsTemplates;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\SmsTemplate;

class SmsTemplateShow extends Component
{
    public SmsTemplate $template;

    public function mount(SmsTemplate $template): void
    {
        $this->template = $template;
    }

    public function render()
    {
        return view('laravel-crm::livewire.sms-templates.sms-template-show');
    }
}
