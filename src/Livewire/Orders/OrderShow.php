<?php

namespace VentureDrake\LaravelCrm\Livewire\Orders;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Pipeline;

class OrderShow extends Component
{
    use AuthorizesRequests, Toast;

    public Order $order;

    public $email;

    public $phone;

    public $address;

    public $taxName;

    public ?Pipeline $pipeline = null;

    protected $listeners = [
        'refreshOrder' => '$refresh',
    ];

    public function mount(Order $order)
    {
        $this->order = $order;

        if ($order->person) {
            $this->email = $order->person->getPrimaryEmail();
            $this->phone = $order->person->getPrimaryPhone();
        }

        if ($order->organization) {
            $this->address = $order->organization->getPrimaryAddress();
        }

        $this->pipeline = Pipeline::where('model', get_class(new Order))->first();
        $this->taxName = app('laravel-crm.settings')->get('tax_name', 'Tax');
    }

    public function delete($id)
    {
        if ($order = Order::find($id)) {
            // Before authorize(), which denies this too, so the user sees why
            // rather than a 403.
            if (! $order->canBeDeleted()) {
                $this->error($order->deleteBlockedReason());

                return;
            }

            $this->authorize('delete', $order);

            $order->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.order_deleted')), redirectTo: route('laravel-crm.orders.index'));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.orders.order-show');
    }
}
