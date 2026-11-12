<?php

namespace VentureDrake\LaravelCrm\Livewire\EmailCampaigns;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\EmailCampaigns\Traits\HasEmailCampaignAudience;
use VentureDrake\LaravelCrm\Mail\EmailCampaignMessage;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\EmailTemplate;
use VentureDrake\LaravelCrm\Services\EmailCampaignService;

class EmailCampaignCreate extends Component
{
    use AuthorizesRequests, HasEmailCampaignAudience, Toast;

    public ?string $name = null;

    public ?int $email_template_id = null;

    public ?string $subject = null;

    public ?string $preview_text = null;

    public ?string $body = null;

    public bool $showPreview = false;

    public string $previewHtml = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'preview_text' => 'nullable|string|max:255',
            'body' => 'required|string',
            ...$this->audienceRules(),
        ];
    }

    protected function authorizeAudienceRead(): void
    {
        $this->authorize('create', EmailCampaign::class);
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

    public function save(bool $confirmed = false)
    {
        $this->authorize('create', EmailCampaign::class);

        $this->validate();

        // Nothing is created until the send can go ahead, so an unconfirmed
        // "Send now" does not leave an orphan draft behind.
        if (! $this->readyToSave($confirmed)) {
            return;
        }

        $service = app(EmailCampaignService::class);

        $campaign = $service->create([
            'name' => $this->name,
            'subject' => $this->subject,
            'preview_text' => $this->preview_text,
            'body' => $this->body,
            'email_template_id' => $this->email_template_id,
            ...$this->audienceData(),
        ]);

        if (($sendAt = $this->sendAt()) !== false) {
            $service->schedule($campaign, $sendAt);
        }

        $this->success(
            $this->savedMessage(),
            redirectTo: route('laravel-crm.email-campaigns.show', $campaign)
        );
    }

    public function openPreview(): void
    {
        $this->previewHtml = EmailCampaignMessage::renderPreview(
            $this->body ?? '',
            $this->preview_text ?? ''
        );
        $this->showPreview = true;
    }

    public function render()
    {
        return view('laravel-crm::livewire.email-campaigns.email-campaign-create', [
            'templates' => $this->templates(),
            'placeholders' => EmailCampaignMessage::availablePlaceholders(),
            ...$this->audienceViewData(),
        ]);
    }
}
