<?php

namespace VentureDrake\LaravelCrm\Livewire\SmsCampaigns;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Models\SmsTemplate;
use VentureDrake\LaravelCrm\Services\SmsCampaignService;
use VentureDrake\LaravelCrm\Sms\SmsCampaignMessage;

class SmsCampaignEdit extends Component
{
    use AuthorizesRequests;
    use Toast;

    public SmsCampaign $campaign;

    public ?string $name = null;

    public ?int $sms_template_id = null;

    public ?string $from = null;

    public ?string $body = null;

    public bool $showPreview = false;

    public string $previewText = '';

    public function mount(SmsCampaign $campaign): void
    {
        if (! $campaign->isEditable()) {
            $this->error(ucfirst(__('laravel-crm::lang.sms_campaign')).' '.__('laravel-crm::lang.not_editable'), redirectTo: route('laravel-crm.sms-campaigns.show', $campaign));

            return;
        }

        $this->campaign = $campaign;
        $this->name = $campaign->name;
        $this->sms_template_id = $campaign->sms_template_id;
        $this->from = $campaign->from;
        $this->body = $campaign->body;
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'from' => 'nullable|string|max:32',
            'body' => 'required|string|max:1530',
        ];
    }

    public function templates()
    {
        return SmsTemplate::orderBy('is_system', 'desc')->orderBy('name')->get();
    }

    public function updatedSmsTemplateId($value): void
    {
        if (! $value) {
            return;
        }

        $template = SmsTemplate::find($value);

        if ($template) {
            $this->body = $template->body;
        }
    }

    public function save(SmsCampaignService $service)
    {
        $this->authorize('update', $this->campaign);

        $this->validate();

        $service->update([
            'name' => $this->name,
            'from' => $this->from,
            'body' => $this->body,
            'sms_template_id' => $this->sms_template_id,
        ], $this->campaign);

        $this->success(
            ucfirst(__('laravel-crm::lang.campaign_stored')),
            redirectTo: route('laravel-crm.sms-campaigns.show', $this->campaign)
        );
    }

    public function openPreview(): void
    {
        $this->previewText = SmsCampaignMessage::renderPreview($this->body ?? '');
        $this->showPreview = true;
    }

    public function getSegmentCountProperty(): int
    {
        return SmsCampaignMessage::segmentCount($this->body ?? '');
    }

    public function render()
    {
        return view('laravel-crm::livewire.sms-campaigns.sms-campaign-edit', [
            'templates' => $this->templates(),
            'placeholders' => SmsCampaignMessage::availablePlaceholders(),
        ]);
    }
}
