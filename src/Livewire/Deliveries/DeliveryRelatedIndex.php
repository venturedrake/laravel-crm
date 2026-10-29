<?php

namespace VentureDrake\LaravelCrm\Livewire\Deliveries;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Support\DeliveryOptions;

class DeliveryRelatedIndex extends Component
{
    use AuthorizesRequests, Toast;

    public Model $model;

    public ?Pipeline $pipeline = null;

    public function mount(Model $model)
    {
        $this->model = $model;
        $this->pipeline = Pipeline::where('model', get_class(new Delivery))->first();
    }

    public function headers(): array
    {
        $headers = [
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->diffForHumans()],
            ['key' => 'delivery_id', 'label' => ucfirst(__('laravel-crm::lang.number'))],
            ['key' => 'reference', 'label' => ucfirst(__('laravel-crm::lang.reference'))],
            ['key' => 'address', 'label' => ucfirst(__('laravel-crm::lang.shipping_address')), 'sortable' => false],
            ['key' => 'delivery_expected', 'label' => ucwords(DeliveryOptions::expectedLabel()), 'format' => fn ($row, $field) => $field?->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d'))],
        ];

        if (DeliveryOptions::showsDeliveredOn()) {
            $headers[] = ['key' => 'delivered_on', 'label' => ucwords(__('laravel-crm::lang.delivered_on')), 'format' => fn ($row, $field) => $field?->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d'))];
        }

        $headers[] = ['key' => 'ownerUser.name', 'label' => 'Owner', 'format' => fn ($row, $field) => $field ?? ucfirst(__('laravel-crm::lang.unallocated')), 'sortable' => false];

        return $headers;
    }

    #[Computed]
    public function deliveries(): Collection
    {
        // Deliveries read their contact details through the parent order, so
        // the preview button's `$delivery->title` costs three queries a row
        // without these.
        return $this->model->deliveries()->with(['order.client', 'order.organization'])->latest()->get();
    }

    public function delete($id): void
    {
        if ($delivery = Delivery::find($id)) {
            $this->authorize('delete', $delivery);

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
