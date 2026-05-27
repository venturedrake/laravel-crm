<?php

namespace VentureDrake\LaravelCrm\Livewire\Portal\PurchaseOrders;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;

class PortalPurchaseOrderLineItems extends Component
{
    public PurchaseOrder $purchaseOrder;

    public string $taxName = 'Tax';

    public function mount(PurchaseOrder $purchaseOrder, string $taxName = 'Tax'): void
    {
        $this->purchaseOrder = $purchaseOrder;
        $this->taxName = $taxName;
    }

    public function render()
    {
        $rows = $this->purchaseOrder->purchaseOrderLines()
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

        return view('laravel-crm::livewire.portal.purchase-orders.portal-purchase-order-line-items', [
            'headers' => $headers,
            'rows' => $rows,
        ]);
    }
}
