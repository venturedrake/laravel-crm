<?php

namespace VentureDrake\LaravelCrm\Livewire\Quotes;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Quotes\Traits\HasQuoteCommon;
use VentureDrake\LaravelCrm\Livewire\Traits\HasOrganizationSuggest;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPersonSuggest;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Quote;

class QuoteEdit extends Component
{
    use AuthorizesRequests, HasOrganizationSuggest;
    use HasPersonSuggest;
    use HasQuoteCommon;

    public ?Quote $quote = null;

    protected $listeners = [
        'model-products-updated' => 'updateProducts',
    ];

    public function mount(Quote $quote)
    {
        $this->authorize('update', $quote);

        // QuoteController::edit turns a locked quote away first; this covers
        // the component mounted any other way.
        if ($reason = $quote->lockReason()) {
            $this->quote = $quote;

            flash()->error($reason);

            $this->redirect(route('laravel-crm.quotes.show', $quote));
            $this->skipRender();

            return;
        }

        $this->mountCommon($quote);

        $this->quote = $quote;
        $this->organization_id = $quote->organization ? $quote->organization->id : null;
        $this->organization_name = $quote->organization ? $quote->organization->name : null;
        $this->person_id = $quote->person ? $quote->person->id : null;
        $this->person_name = $quote->person ? $quote->person->name : null;
        $this->title = $quote->title;
        $this->description = $quote->description;
        $this->reference = $quote->reference;
        $this->currency = $quote->currency;
        $this->issue_at = $quote->hasStoredDate('issue_at') ? $quote->issue_at->format('Y-m-d') : null;
        $this->expire_at = $quote->validUntil()?->format('Y-m-d');
        $this->terms = $quote->terms;
        $this->pipeline_stage_id = $quote->pipelineStage->id ?? null;
        $this->labels = $quote->labels->pluck('id')->toArray();
        $this->user_owner_id = $quote->ownerUser->id ?? null;

        $this->sub_total = $quote->subtotal / 100;
        $this->tax = $quote->tax / 100;
        $this->total = $quote->total / 100;

        $this->loadCustomFields($quote);
    }

    public function save()
    {
        $this->authorize('update', $this->quote);

        // Accepted or ordered since the page was opened.
        if ($reason = $this->quote->fresh()->lockReason()) {
            $this->error($reason);

            return;
        }

        $this->validate();

        // Create a request object to pass to services
        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        // One transaction, so a failure part-way - in the service or in
        // the custom fields - leaves no contact, document or lines behind.
        // The service refuses an accepted or ordered quote; the form has no
        // field for that error, so it is shown as a toast.
        try {
            DB::transaction(function () use ($request) {
                // First, before any plain read fixes this transaction's
                // snapshot - see InvoiceService::lockForEdit().
                $this->quoteService->lockForEdit($this->quote);

                if ($this->person_name && ! $this->person_id) {
                    $person = $this->personService->createFromRelated($request);
                } elseif ($this->person_id) {
                    $person = Person::find($this->person_id);
                }

                if ($this->organization_name && ! $this->organization_id) {
                    $organization = $this->organizationService->createFromRelated($request);
                } elseif ($this->organization_id) {
                    $organization = Organization::find($this->organization_id);
                }

                $this->personService->linkToOrganization($person ?? null, $organization ?? null);

                $this->quoteService->update($request, $this->quote, $person ?? null, $organization ?? null);

                $this->saveCustomFields($this->quote->fresh());
            });
        } catch (ValidationException $e) {
            $this->error(collect($e->errors())->flatten()->first());

            return;
        }

        $this->success(
            ucfirst(trans('laravel-crm::lang.quote_updated_successfully')),
            redirectTo: route('laravel-crm.quotes.show', $this->quote)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.quotes.quote-edit');
    }
}
