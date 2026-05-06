<?php

namespace VentureDrake\LaravelCrm\Livewire\EmailCampaigns;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Mail\EmailCampaignMessage;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\EmailTemplate;
use VentureDrake\LaravelCrm\Services\EmailCampaignService;

class EmailCampaignEdit extends Component
{
    use Toast;

    public EmailCampaign $campaign;

    public ?string $name = null;

    public ?int $email_template_id = null;

    public ?string $subject = null;

    public ?string $preview_text = null;

    public ?string $body = null;

    public bool $showPreview = false;

    public string $previewHtml = '';

    public function mount(EmailCampaign $campaign): void
    {
        if (! $campaign->isEditable()) {
            $this->error(ucfirst(__('laravel-crm::lang.email_campaign')).' '.__('laravel-crm::lang.not_editable'), redirectTo: route('laravel-crm.email-campaigns.show', $campaign));

            return;
        }

        $this->campaign = $campaign;
        $this->name = $campaign->name;
        $this->email_template_id = $campaign->email_template_id;
        $this->subject = $campaign->subject;
        $this->preview_text = $campaign->preview_text;
        $this->body = $campaign->body;
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

    public function templates()
    {
        return EmailTemplate::orderBy('is_system', 'desc')->orderBy('name')->get();
    }

    public function updatedEmailTemplateId($value): void
    {
        if (! $value) {
            return;
        }

        $template = EmailTemplate::find($value);

        if ($template) {
            $this->subject = $template->subject;
            $this->preview_text = $template->preview_text;
            $this->body = $template->body;
        }
    }

    public function save(EmailCampaignService $service)
    {
        $this->validate();

        $service->update([
            'name' => $this->name,
            'subject' => $this->subject,
            'preview_text' => $this->preview_text,
            'body' => $this->body,
            'email_template_id' => $this->email_template_id,
        ], $this->campaign);

        $this->success(
            ucfirst(__('laravel-crm::lang.campaign_stored')),
            redirectTo: route('laravel-crm.email-campaigns.show', $this->campaign)
        );
    }

    public function openPreview(): void
    {
        $this->previewHtml = EmailCampaignMessage::renderPreview(
            $this->body ?? '',
            $this->preview_text ?? '',
            $this->campaign->team_id
        );
        $this->showPreview = true;
    }

    public function render()
    {
        return view('laravel-crm::livewire.email-campaigns.email-campaign-edit', [
            'templates' => $this->templates(),
            'placeholders' => EmailCampaignMessage::availablePlaceholders(),
        ]);
    }
}
