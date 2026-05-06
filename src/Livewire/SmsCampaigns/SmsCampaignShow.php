<?php

namespace VentureDrake\LaravelCrm\Livewire\SmsCampaigns;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Services\SmsCampaignService;
use VentureDrake\LaravelCrm\Sms\SmsCampaignMessage;

class SmsCampaignShow extends Component
{
    use AuthorizesRequests;
    use Toast;
    use WithPagination;

    public SmsCampaign $campaign;

    public bool $showPreview = false;

    public string $previewText = '';

    public function mount(SmsCampaign $campaign): void
    {
        $this->campaign = $campaign;
    }

    public function recipients(): LengthAwarePaginator
    {
        return $this->campaign->recipients()
            ->with('phone')
            ->orderBy('id', 'desc')
            ->paginate(25);
    }

    public function recipientHeaders(): array
    {
        return [
            ['key' => 'number', 'label' => ucfirst(__('laravel-crm::lang.number'))],
            ['key' => 'status', 'label' => ucfirst(__('laravel-crm::lang.status'))],
            ['key' => 'sent_at', 'label' => ucfirst(__('laravel-crm::lang.sent_at'))],
            ['key' => 'clicks_count', 'label' => ucfirst(__('laravel-crm::lang.clicks'))],
            ['key' => 'unsubscribed_at', 'label' => ucfirst(__('laravel-crm::lang.unsubscribed'))],
        ];
    }

    public function openPreview(): void
    {
        $this->previewText = SmsCampaignMessage::renderPreview($this->campaign->body ?? '');
        $this->showPreview = true;
    }

    public function cancel(SmsCampaignService $service): void
    {
        $this->authorize('update', $this->campaign);

        $service->cancel($this->campaign);
        $this->campaign->refresh();
        $this->success(ucfirst(__('laravel-crm::lang.sms_campaign')).' '.__('laravel-crm::lang.cancelled'));
    }

    public function render()
    {
        return view('laravel-crm::livewire.sms-campaigns.sms-campaign-show', [
            'recipients' => $this->recipients(),
            'recipientHeaders' => $this->recipientHeaders(),
        ]);
    }
}
