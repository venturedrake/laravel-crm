<?php

namespace VentureDrake\LaravelCrm\Livewire\Quotes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use VentureDrake\LaravelCrm\Livewire\PipelineBoard;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\QuoteStateService;

class QuoteBoard extends PipelineBoard
{
    public $model = 'quote';

    #[Url]
    public string $search = '';

    #[Url]
    public ?array $user_id = [];

    #[Url]
    public ?array $label_id = [];

    public bool $showFilters = false;

    protected $listeners = [
        'refreshQuote' => '$refresh',
    ];

    protected function boardModel(): string
    {
        return Quote::class;
    }

    protected function amountColumn(): string
    {
        return 'total';
    }

    protected function searchColumns(): array
    {
        return ['title', 'reference'];
    }

    protected function cardColumns(): array
    {
        return [
            'id', 'quote_id', 'number', 'title', 'currency', 'accepted_at', 'rejected_at',
            'subtotal', 'discount', 'tax', 'adjustments', 'total',
        ];
    }

    /**
     * A card checks its amounts against its lines.
     */
    protected function eagerLoads(): array
    {
        return [...parent::eagerLoads(), 'quoteProducts:id,quote_id,product_id,price,quantity'];
    }

    protected function cardQuery(): Builder
    {
        return parent::cardQuery()->withExists('orders');
    }

    /**
     * Only a quote that was accepted and ordered asks orderComplete(), so the order
     * lines are loaded for those cards rather than for the whole page.
     */
    protected function prepareCards(Collection $records): void
    {
        $records
            ->filter(fn (Quote $quote) => $quote->accepted_at && $quote->hasOrders())
            ->load('orders.orderProducts');
    }

    public function delete($id)
    {
        if ($quote = Quote::find($id)) {
            // Before authorize(), which denies this too, so the user sees why
            // rather than a 403.
            if (! $quote->canBeDeleted()) {
                $this->error($quote->deleteBlockedReason());

                return;
            }

            $this->authorize('delete', $quote);

            $quote->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.quote_deleted')));
        }
    }

    public function accept($id): void
    {
        if ($quote = Quote::find($id)) {
            $this->authorize('update', $quote);

            app(QuoteStateService::class)->accept($quote);

            $this->success(ucfirst(trans('laravel-crm::lang.quote_accepted')));
        }
    }

    public function reject($id): void
    {
        if ($quote = Quote::find($id)) {
            $this->authorize('update', $quote);

            app(QuoteStateService::class)->reject($quote);

            $this->success(ucfirst(trans('laravel-crm::lang.quote_rejected')));
        }
    }

    public function unaccept($id): void
    {
        if ($quote = Quote::find($id)) {
            $this->authorize('update', $quote);

            // An order was made from what was accepted.
            if (! app(QuoteStateService::class)->unaccept($quote)) {
                $this->error(ucfirst(trans('laravel-crm::lang.quote_locked_ordered')));

                return;
            }

            $this->success(ucfirst(trans('laravel-crm::lang.quote_unaccepted')));
        }
    }

    public function unreject($id): void
    {
        if ($quote = Quote::find($id)) {
            $this->authorize('update', $quote);

            app(QuoteStateService::class)->unreject($quote);

            $this->success(ucfirst(trans('laravel-crm::lang.quote_unrejected')));
        }
    }
}
