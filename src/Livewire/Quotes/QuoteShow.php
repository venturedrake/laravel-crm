<?php

namespace VentureDrake\LaravelCrm\Livewire\Quotes;

use Carbon\Carbon;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Quote;

class QuoteShow extends Component
{
    use Toast;

    public Quote $quote;

    public $email;

    public $phone;

    public $address;

    public $taxName;

    public ?Pipeline $pipeline = null;

    protected $listeners = [
        'refreshQuote' => '$refresh',
    ];

    public function mount(Quote $quote)
    {
        $this->quote = $quote;

        if ($quote->person) {
            $this->email = $quote->person->getPrimaryEmail();
            $this->phone = $quote->person->getPrimaryPhone();
        }

        if ($quote->organization) {
            $this->address = $quote->organization->getPrimaryAddress();
        }

        $this->pipeline = Pipeline::where('model', get_class(new Quote))->first();
        $this->taxName = app('laravel-crm.settings')->get('tax_name', 'Tax');
    }

    public function delete($id)
    {
        if ($quote = Quote::find($id)) {
            $quote->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.quote_deleted')), redirectTo: route('laravel-crm.quotes.index'));
        }
    }

    public function accept($id): void
    {
        if ($quote = Quote::find($id)) {
            $quote->update([
                'accepted_at' => Carbon::now(),
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Accepted')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.quote_accepted')));
            $this->dispatch('refreshQuote');
        }
    }

    public function reject($id): void
    {
        if ($quote = Quote::find($id)) {
            $quote->update([
                'rejected_at' => Carbon::now(),
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Rejected')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.quote_rejected')));
            $this->dispatch('refreshQuote');
        }
    }

    public function unaccept($id): void
    {
        if ($quote = Quote::find($id)) {
            $quote->update([
                'accepted_at' => null,
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Draft')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.quote_unaccepted')));
            $this->dispatch('refreshQuote');
        }
    }

    public function unreject($id): void
    {
        if ($quote = Quote::find($id)) {
            $quote->update([
                'rejected_at' => null,
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Draft')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.quote_unrejected')));
            $this->dispatch('refreshQuote');
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.quotes.quote-show');
    }
}
