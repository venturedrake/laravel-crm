<?php

namespace VentureDrake\LaravelCrm\Livewire\PurchaseOrders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;

class PurchaseOrderRelatedIndex extends Component
{
    use Toast;

    public Model $model;

    public ?Pipeline $pipeline = null;

    public function mount(Model $model)
    {
        $this->model = $model;
        $this->pipeline = Pipeline::where('model', get_class(new PurchaseOrder))->first();
    }

    public function headers(): array
    {
        $headers = [
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->diffForHumans()],
            ['key' => 'purchase_order_id', 'label' => ucfirst(__('laravel-crm::lang.number'))],
            ['key' => 'reference', 'label' => ucfirst(__('laravel-crm::lang.reference'))],
            ['key' => 'issue_date', 'label' => ucwords(__('laravel-crm::lang.issue_date')), 'format' => fn ($row, $field) => $field?->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d'))],
            ['key' => 'delivery_date', 'label' => ucwords(__('laravel-crm::lang.delivery_date')), 'format' => fn ($row, $field) => $field?->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d'))],
            ['key' => 'total', 'label' => ucfirst(__('laravel-crm::lang.amount')), 'format' => fn ($row, $field) => money($field, $row->currency)],
        ];

        return $headers;
    }

    #[Computed]
    public function purchaseOrders(): Collection
    {
        return $this->model->purchaseOrders()->latest()->get();
    }

    public function delete($id): void
    {
        if ($purchaseOrder = PurchaseOrder::find($id)) {
            $purchaseOrder->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.purchase_order_deleted')));
            $this->dispatch('$refresh');
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.purchase-orders.purchase-order-related-index', [
            'headers' => $this->headers(),
        ]);
    }
}
