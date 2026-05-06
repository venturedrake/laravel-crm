<?php

namespace VentureDrake\LaravelCrm\Livewire\EmailCampaigns;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Mail\EmailCampaignMessage;
use VentureDrake\LaravelCrm\Models\EmailTemplate;
use VentureDrake\LaravelCrm\Services\EmailCampaignService;

class EmailCampaignCreate extends Component
{
    use Toast;

    public ?string $name = null;

    public ?int $email_template_id = null;

    public ?string $subject = null;

    public ?string $body = null;

    public string $send_mode = 'now';

    public ?string $scheduled_at = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'send_mode' => 'required|in:now,schedule',
            'scheduled_at' => 'required_if:send_mode,schedule|nullable|date|after:now',
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
            $this->body = $template->body;
        }
    }

    public function save(EmailCampaignService $service)
    {
        $this->validate();

        $campaign = $service->create([
            'name' => $this->name,
            'subject' => $this->subject,
            'body' => $this->body,
            'email_template_id' => $this->email_template_id,
        ]);

        $service->schedule($campaign, $this->send_mode === 'schedule' ? $this->scheduled_at : null);

        $this->success(
            ucfirst(__('laravel-crm::lang.campaign_stored')),
            redirectTo: route('laravel-crm.email-campaigns.show', $campaign)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.email-campaigns.email-campaign-create', [
            'templates' => $this->templates(),
            'placeholders' => EmailCampaignMessage::availablePlaceholders(),
        ]);
    }
}
