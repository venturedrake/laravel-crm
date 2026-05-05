<?php

namespace VentureDrake\LaravelCrm\Livewire\EmailCampaigns;

use Livewire\Component;
use Mary\Traits\Toast;
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

    public ?string $body = null;

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
        $this->body = $campaign->body;
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
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
            $this->body = $template->body;
        }
    }

    public function save(EmailCampaignService $service)
    {
        $this->validate();

        $service->update([
            'name' => $this->name,
            'subject' => $this->subject,
            'body' => $this->body,
            'email_template_id' => $this->email_template_id,
        ], $this->campaign);

        $this->success(
            ucfirst(__('laravel-crm::lang.campaign_stored')),
            redirectTo: route('laravel-crm.email-campaigns.show', $this->campaign)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.email-campaigns.email-campaign-edit', [
            'templates' => $this->templates(),
        ]);
    }
}
