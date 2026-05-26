<?php

namespace VentureDrake\LaravelCrm\Livewire\Portal\Invoices;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Invoice;

class PortalInvoiceLineItems extends Component
{
    public Invoice $invoice;

    public string $taxName = 'Tax';

    public function mount(Invoice $invoice, string $taxName = 'Tax'): void
    {
        $this->invoice = $invoice;
        $this->taxName = $taxName;
    }

    public function render()
    {
        $rows = $this->invoice->invoiceLines()
            ->whereNotNull('product_id')
            ->orderBy('order', 'asc')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($line) => [
                'item' => $line->product->name ?? null,
                'price' => (string) money($line->price ?? null, $line->currency),
                'quantity' => $line->quantity,
                'tax' => (string) money($line->tax_amount ?? null, $line->currency),
                'amount' => (string) money($line->amount ?? null, $line->currency),
                'comments' => $line->comments,
            ])
            ->all();

        $headers = [
            ['key' => 'item', 'label' => ucfirst(__('laravel-crm::lang.item'))],
            ['key' => 'price', 'label' => ucfirst(__('laravel-crm::lang.price'))],
            ['key' => 'quantity', 'label' => ucfirst(__('laravel-crm::lang.quantity'))],
            ['key' => 'tax', 'label' => $this->taxName],
            ['key' => 'amount', 'label' => ucfirst(__('laravel-crm::lang.amount'))],
            ['key' => 'comments', 'label' => ucfirst(__('laravel-crm::lang.comments'))],
        ];

        return view('laravel-crm::livewire.portal.invoices.portal-invoice-line-items', [
            'headers' => $headers,
            'rows' => $rows,
        ]);
    }
}
