<?php

namespace VentureDrake\LaravelCrm\Livewire\Deliveries;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Pipeline;

class DeliveryRelatedIndex extends Component
{
    use Toast;

    public Model $model;

    public ?Pipeline $pipeline = null;

    public function mount(Model $model)
    {
        $this->model = $model;
        $this->pipeline = Pipeline::where('model', get_class(new Delivery))->first();
    }

    public function headers(): array
    {
        return [
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->diffForHumans()],
            ['key' => 'delivery_id', 'label' => ucfirst(__('laravel-crm::lang.number'))],
            ['key' => 'reference', 'label' => ucfirst(__('laravel-crm::lang.reference'))],
            ['key' => 'address', 'label' => ucfirst(__('laravel-crm::lang.shipping_address')), 'sortable' => false],
            ['key' => 'delivery_expected', 'label' => ucwords(__('laravel-crm::lang.delivery_expected')), 'format' => fn ($row, $field) => $field?->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d'))],
            ['key' => 'delivered_on', 'label' => ucwords(__('laravel-crm::lang.delivered_on')), 'format' => fn ($row, $field) => $field?->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d'))],
            ['key' => 'ownerUser.name', 'label' => 'Owner', 'format' => fn ($row, $field) => $field ?? ucfirst(__('laravel-crm::lang.unallocated')), 'sortable' => false],
        ];
    }

    #[Computed]
    public function deliveries(): Collection
    {
        return $this->model->deliveries()->latest()->get();
    }

    public function delete($id): void
    {
        if ($delivery = Delivery::find($id)) {
            $delivery->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.delivery_deleted')));
            $this->dispatch('$refresh');
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.deliveries.delivery-related-index', [
            'headers' => $this->headers(),
        ]);
    }
}
