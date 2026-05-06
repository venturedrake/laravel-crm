<?php

namespace VentureDrake\LaravelCrm\Livewire\EmailCampaigns;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Mail\EmailCampaignMessage;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Services\EmailCampaignService;

class EmailCampaignShow extends Component
{
    use AuthorizesRequests;
    use Toast;
    use WithPagination;

    public EmailCampaign $campaign;

    public bool $showPreview = false;

    public string $previewHtml = '';

    public function mount(EmailCampaign $campaign): void
    {
        $this->campaign = $campaign;
    }

    public function recipients(): LengthAwarePaginator
    {
        return $this->campaign->recipients()
            ->orderBy('id', 'desc')
            ->paginate(25);
    }

    public function recipientHeaders(): array
    {
        return [
            ['key' => 'address', 'label' => ucfirst(__('laravel-crm::lang.email'))],
            ['key' => 'status', 'label' => ucfirst(__('laravel-crm::lang.status'))],
            ['key' => 'sent_at', 'label' => ucfirst(__('laravel-crm::lang.sent_at'))],
            ['key' => 'opens_count', 'label' => ucfirst(__('laravel-crm::lang.opens'))],
            ['key' => 'clicks_count', 'label' => ucfirst(__('laravel-crm::lang.clicks'))],
            ['key' => 'unsubscribed_at', 'label' => ucfirst(__('laravel-crm::lang.unsubscribed'))],
        ];
    }

    public function openPreview(): void
    {
        $this->previewHtml = EmailCampaignMessage::renderPreview(
            $this->campaign->body ?? '',
            $this->campaign->preview_text ?? '',
            $this->campaign->team_id
        );
        $this->showPreview = true;
    }

    public function delete($id): void
    {
        if ($campaign = EmailCampaign::find($id)) {
            $this->authorize('delete', $campaign);
            $campaign->delete();
            $this->success(
                ucfirst(__('laravel-crm::lang.email_campaign')).' '.__('laravel-crm::lang.deleted'),
                redirectTo: route('laravel-crm.email-campaigns.index')
            );
        }
    }

    public function cancel(EmailCampaignService $service): void
    {
        $service->cancel($this->campaign);
        $this->campaign->refresh();
        $this->success(ucfirst(__('laravel-crm::lang.email_campaign')).' '.__('laravel-crm::lang.cancelled'));
    }

    public function render()
    {
        return view('laravel-crm::livewire.email-campaigns.email-campaign-show', [
            'recipients' => $this->recipients(),
            'recipientHeaders' => $this->recipientHeaders(),
        ]);
    }
}
