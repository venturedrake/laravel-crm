<?php

namespace VentureDrake\LaravelCrm\Livewire\SmsCampaigns;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Models\SmsTemplate;
use VentureDrake\LaravelCrm\Services\ClickSendService;
use VentureDrake\LaravelCrm\Services\SmsCampaignService;
use VentureDrake\LaravelCrm\Sms\SmsCampaignMessage;

class SmsCampaignCreate extends Component
{
    use AuthorizesRequests;
    use Toast;

    public ?string $name = null;

    public ?int $sms_template_id = null;

    public ?string $from = null;

    public ?string $body = null;

    public string $send_mode = 'now';

    public ?string $scheduled_at = null;

    public bool $showPreview = false;

    public string $previewText = '';

    public function mount(ClickSendService $clickSend): void
    {
        $this->from = $clickSend->defaultFrom();
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'from' => 'nullable|string|max:32',
            'body' => 'required|string|max:1530',
            'send_mode' => 'required|in:now,schedule',
            'scheduled_at' => 'required_if:send_mode,schedule|nullable|date|after:now',
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
        $this->authorize('create', SmsCampaign::class);

        $this->validate();

        $campaign = $service->create([
            'name' => $this->name,
            'from' => $this->from,
            'body' => $this->body,
            'sms_template_id' => $this->sms_template_id,
        ]);

        $service->schedule($campaign, $this->send_mode === 'schedule' ? $this->scheduled_at : null);

        $this->success(
            ucfirst(__('laravel-crm::lang.campaign_stored')),
            redirectTo: route('laravel-crm.sms-campaigns.show', $campaign)
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
        return view('laravel-crm::livewire.sms-campaigns.sms-campaign-create', [
            'templates' => $this->templates(),
            'placeholders' => SmsCampaignMessage::availablePlaceholders(),
        ]);
    }
}
