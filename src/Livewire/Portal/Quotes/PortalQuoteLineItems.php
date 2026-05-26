<?php

namespace VentureDrake\LaravelCrm\Livewire\Portal\Quotes;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Quote;

class PortalQuoteLineItems extends Component
{
    public Quote $quote;

    public function mount(Quote $quote): void
    {
        $this->quote = $quote;
    }

    public function render()
    {
        $rows = $this->quote->quoteProducts()
            ->whereNotNull('product_id')
            ->orderBy('order', 'asc')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($qp) => [
                'item' => $qp->product->name ?? null,
                'price' => (string) money($qp->price ?? null, $qp->currency),
                'quantity' => $qp->quantity,
                'amount' => (string) money($qp->amount ?? null, $qp->currency),
                'comments' => $qp->comments,
            ])
            ->all();

        $headers = [
            ['key' => 'item', 'label' => ucfirst(__('laravel-crm::lang.item'))],
            ['key' => 'price', 'label' => ucfirst(__('laravel-crm::lang.price'))],
            ['key' => 'quantity', 'label' => ucfirst(__('laravel-crm::lang.quantity'))],
            ['key' => 'amount', 'label' => ucfirst(__('laravel-crm::lang.amount'))],
            ['key' => 'comments', 'label' => ucfirst(__('laravel-crm::lang.comments'))],
        ];

        return view('laravel-crm::livewire.portal.quotes.portal-quote-line-items', [
            'headers' => $headers,
            'rows' => $rows,
        ]);
    }
}
