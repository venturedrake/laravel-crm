<?php

namespace VentureDrake\LaravelCrm\Livewire\Orders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Pipeline;

class OrderRelatedIndex extends Component
{
    use Toast;

    public Model $model;

    public ?Pipeline $pipeline = null;

    public function mount(Model $model)
    {
        $this->model = $model;
        $this->pipeline = Pipeline::where('model', get_class(new Order))->first();
    }

    public function headers(): array
    {
        $headers = [
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->diffForHumans()],
            ['key' => 'order_id', 'label' => ucfirst(__('laravel-crm::lang.number'))],
            ['key' => 'reference', 'label' => ucfirst(__('laravel-crm::lang.reference'))],
            ['key' => 'labels', 'label' => ucfirst(__('laravel-crm::lang.labels')), 'format' => fn ($row, $field) => $field],
            ['key' => 'subtotal', 'label' => ucfirst(__('laravel-crm::lang.sub_total')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'discount', 'label' => ucfirst(__('laravel-crm::lang.discount')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'tax', 'label' => ucfirst(__('laravel-crm::lang.tax')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'adjustments', 'label' => ucfirst(__('laravel-crm::lang.adjustment')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'total', 'label' => ucfirst(__('laravel-crm::lang.total')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'ownerUser.name', 'label' => 'Owner', 'format' => fn ($row, $field) => $field ?? ucfirst(__('laravel-crm::lang.unallocated')), 'sortable' => false],
        ];

        return $headers;
    }

    #[Computed]
    public function orders(): Collection
    {
        return $this->model->orders()->latest()->get();
    }

    public function delete($id): void
    {
        if ($order = Order::find($id)) {
            $order->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.order_deleted')));
            $this->dispatch('$refresh');
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.orders.order-related-index', [
            'headers' => $this->headers(),
        ]);
    }
}
