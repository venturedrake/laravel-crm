<?php

namespace VentureDrake\LaravelCrm\Livewire\Deals;

use Carbon\Carbon;
use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Deal;

class DealShow extends Component
{
    public $deal;

    public $email;

    public $phone;

    public $address;

    protected $listeners = [
        'refreshDeal' => '$refresh',
    ];

    public function mount(Deal $deal)
    {
        $this->deal = $deal;

        if ($deal->person) {
            $this->email = $deal->person->getPrimaryEmail();
            $this->phone = $deal->person->getPrimaryPhone();
        }

        if ($deal->organization) {
            $this->address = $deal->organization->getPrimaryAddress();
        }
    }

    public function won($id)
    {
        if ($deal = Deal::find($id)) {
            $deal->update([
                'closed_status' => 'won',
                'closed_at' => Carbon::now(),
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Closed Won')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.deal_won')));
            $this->dispatch('refreshDeal');
        }
    }

    public function lost($id)
    {
        if ($deal = Deal::find($id)) {
            $deal->update([
                'closed_status' => 'lost',
                'closed_at' => Carbon::now(),
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Closed Lost')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.deal_lost')));
            $this->dispatch('refreshDeal');
        }
    }

    public function reopen($id)
    {
        if ($deal = Deal::find($id)) {
            $deal->update([
                'closed_status' => null,
                'closed_at' => null,
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Pending')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.deal_reopened')));
            $this->dispatch('refreshDeal');
        }
    }

    public function delete($id)
    {
        if ($deal = Deal::find($id)) {
            $deal->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.deal_deleted')), redirectTo: route('laravel-crm.deals.index'));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.deals.deal-show');
    }
}
