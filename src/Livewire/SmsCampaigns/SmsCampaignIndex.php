<?php

namespace VentureDrake\LaravelCrm\Livewire\SmsCampaigns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Services\ClickSendService;

class SmsCampaignIndex extends Component
{
    use AuthorizesRequests;
    use Toast;
    use WithPagination;

    public $layout = 'index';

    #[Url]
    public string $search = '';

    #[Url]
    public ?string $status = '';

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public function statuses(): array
    {
        return [
            ['id' => 'draft', 'name' => ucfirst(__('laravel-crm::lang.campaign_status_draft'))],
            ['id' => 'scheduled', 'name' => ucfirst(__('laravel-crm::lang.campaign_status_scheduled'))],
            ['id' => 'sending', 'name' => ucfirst(__('laravel-crm::lang.campaign_status_sending'))],
            ['id' => 'sent', 'name' => ucfirst(__('laravel-crm::lang.campaign_status_sent'))],
            ['id' => 'cancelled', 'name' => ucfirst(__('laravel-crm::lang.campaign_status_cancelled'))],
            ['id' => 'failed', 'name' => ucfirst(__('laravel-crm::lang.campaign_status_failed'))],
        ];
    }

    public function headers(): array
    {
        return [
            ['key' => 'campaign_id', 'label' => ucfirst(__('laravel-crm::lang.number'))],
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'from', 'label' => ucfirst(__('laravel-crm::lang.sender_id'))],
            ['key' => 'status', 'label' => ucfirst(__('laravel-crm::lang.status'))],
            ['key' => 'total_recipients', 'label' => ucfirst(__('laravel-crm::lang.recipients'))],
            ['key' => 'scheduled_at', 'label' => ucfirst(__('laravel-crm::lang.scheduled_at'))],
            ['key' => 'sent_at', 'label' => ucfirst(__('laravel-crm::lang.sent_at'))],
        ];
    }

    public function campaigns(): LengthAwarePaginator
    {
        return SmsCampaign::query()
            ->when($this->search, function (Builder $q) {
                $q->where(function ($q) {
                    $q->where('name', 'like', "%$this->search%")
                        ->orWhere('campaign_id', 'like', "%$this->search%");
                });
            })
            ->when($this->status, fn (Builder $q) => $q->where('status', $this->status))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($campaign = SmsCampaign::find($id)) {
            $this->authorize('delete', $campaign);
            $campaign->delete();
            $this->success(ucfirst(__('laravel-crm::lang.sms_campaign')).' '.__('laravel-crm::lang.deleted'));
        }
    }

    public function render(ClickSendService $clickSend)
    {
        return view('laravel-crm::livewire.sms-campaigns.sms-campaign-index', [
            'headers' => $this->headers(),
            'campaigns' => $this->campaigns(),
            'statuses' => $this->statuses(),
            'clickSendConfigured' => $clickSend->isConfigured(),
        ]);
    }
}
