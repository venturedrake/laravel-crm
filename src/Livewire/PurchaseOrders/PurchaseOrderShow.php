<?php

namespace VentureDrake\LaravelCrm\Livewire\PurchaseOrders;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;

class PurchaseOrderShow extends Component
{
    use AuthorizesRequests, Toast;

    public PurchaseOrder $purchaseOrder;

    public $email;

    public $phone;

    public $address;

    public $taxName;

    public $timezone;

    public ?Pipeline $pipeline = null;

    protected $listeners = [
        'refreshPurchaseOrder' => '$refresh',
        // The Sent badge here goes stale the moment the layout's get-link
        // modal ticks "mark as sent" -- that write runs in a different
        // Livewire root, so nothing re-renders this page on its own.
        'crm-get-link-sent' => '$refresh',
    ];

    public function mount(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;

        if ($purchaseOrder->person) {
            $this->email = $purchaseOrder->person->getPrimaryEmail();
            $this->phone = $purchaseOrder->person->getPrimaryPhone();
        }

        if ($purchaseOrder->organization) {
            $this->address = $purchaseOrder->organization->getPrimaryAddress();
        }

        $this->pipeline = Pipeline::where('model', get_class(new PurchaseOrder))->first();
        $this->taxName = app('laravel-crm.settings')->get('tax_name', 'Tax');
        $this->timezone = app('laravel-crm.settings')->get('timezone', 'UTC');
    }

    public function delete($id)
    {
        if ($purchaseOrder = PurchaseOrder::find($id)) {
            $this->authorize('delete', $purchaseOrder);

            $purchaseOrder->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.purchase_order_deleted')), redirectTo: route('laravel-crm.purchase-orders.index'));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.purchase-orders.purchase-order-show');
    }
}
