<?php

namespace VentureDrake\LaravelCrm\Livewire\EmailTemplates;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Mail\EmailCampaignMessage;
use VentureDrake\LaravelCrm\Models\EmailTemplate;
use VentureDrake\LaravelCrm\Services\EmailTemplateService;

class EmailTemplateEdit extends Component
{
    use Toast;

    public EmailTemplate $template;

    public ?string $name = null;

    public ?string $subject = null;

    public ?string $preview_text = null;

    public ?string $body = null;

    public function mount(EmailTemplate $template): void
    {
        if ($template->is_system) {
            $this->error(ucfirst(__('laravel-crm::lang.email_template')).' '.__('laravel-crm::lang.is_system_readonly'), redirectTo: route('laravel-crm.email-templates.show', $template));

            return;
        }

        $this->template = $template;
        $this->name = $template->name;
        $this->subject = $template->subject;
        $this->preview_text = $template->preview_text;
        $this->body = $template->body;
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'preview_text' => 'nullable|string|max:255',
            'body' => 'required|string',
        ];
    }

    public function save(EmailTemplateService $service)
    {
        $this->validate();

        $service->update([
            'name' => $this->name,
            'subject' => $this->subject,
            'preview_text' => $this->preview_text,
            'body' => $this->body,
        ], $this->template);

        $this->success(
            ucfirst(__('laravel-crm::lang.email_template')).' '.__('laravel-crm::lang.updated'),
            redirectTo: route('laravel-crm.email-templates.show', $this->template)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.email-templates.email-template-edit', [
            'placeholders' => EmailCampaignMessage::availablePlaceholders(),
        ]);
    }
}
