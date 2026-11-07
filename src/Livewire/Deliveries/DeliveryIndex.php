<?php

namespace VentureDrake\LaravelCrm\Livewire\Deliveries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\HasUserOwnerFilter;
use VentureDrake\LaravelCrm\Livewire\Traits\SortsTable;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Support\DeliveryOptions;
use VentureDrake\LaravelCrm\Support\RecordSearch;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class DeliveryIndex extends Component
{
    use AuthorizesRequests, ClearsProperties, HasUserOwnerFilter, ResetsPaginationWhenPropsChanges, Toast, WithPagination;
    use SortsTable;

    public $layout = 'index';

    #[Url]
    public string $search = '';

    #[Url]
    public ?array $user_id = [];

    #[Url]
    public ?array $label_id = [];

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public bool $showFilters = false;

    public ?Pipeline $pipeline = null;

    public function mount()
    {
        $this->pipeline = Pipeline::where('model', get_class(new Delivery))->first();
    }

    public function filterCount(): int
    {
        return (count($this->user_id) > 0 ? 1 : 0) + ($this->label_id ? 1 : 0);
    }

    /**
     * Filter drawer options, computed so a debounced search keystroke reuses
     * them rather than re-querying labels on every render.
     */
    #[Computed]
    public function labels(): Collection
    {
        return Label::select('id', 'name')->get();
    }

    public function headers()
    {
        $headers = [
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->diffForHumans()],
            ['key' => 'delivery_id', 'label' => ucfirst(__('laravel-crm::lang.number'))],
            ['key' => 'order.reference', 'sortable' => false, 'label' => ucfirst(__('laravel-crm::lang.reference'))],
        ];

        if (auth()->user()->can('view crm orders')) {
            $headers = array_merge($headers, [
                ['key' => 'order', 'label' => ucfirst(__('laravel-crm::lang.order')), 'disableLink' => true, 'sortable' => false],
            ]);
        }

        $headers = array_merge($headers, [
            ['key' => 'order.person.name', 'label' => ucfirst(__('laravel-crm::lang.contact')), 'sortable' => false],
            ['key' => 'order.organization.name', 'label' => ucfirst(__('laravel-crm::lang.organization')), 'sortable' => false],
            /* ['key' => 'pipeline_stage', 'label' => ucfirst(__('laravel-crm::lang.stage'))], */
            ['key' => 'address', 'label' => ucfirst(__('laravel-crm::lang.shipping_address')), 'sortable' => false],
            ['key' => 'delivery_expected', 'label' => ucwords(DeliveryOptions::expectedLabel()), 'format' => fn ($row, $field) => ($field) ? $field->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null],
        ]
        );

        if (DeliveryOptions::showsDeliveredOn()) {
            $headers[] = ['key' => 'delivered_on', 'label' => ucwords(__('laravel-crm::lang.delivered_on')), 'format' => fn ($row, $field) => ($field) ? $field->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null];
        }

        $headers[] = ['key' => 'ownerUser.name', 'label' => 'Owner', 'format' => fn ($row, $field) => $field ?? ucfirst(__('laravel-crm::lang.unallocated')), 'sortable' => false];

        return $headers;
    }

    public function deliveries(): LengthAwarePaginator
    {
        // The order column and the preview button's `$delivery->title` both
        // read through the parent order, so without these the table costs
        // three extra queries per row. addresses feeds the shipping address
        // cell and ownerUser the dotted `ownerUser.name` header key.
        return Delivery::with(['order.client', 'order.person', 'order.organization', 'ownerUser', 'addresses'])
            ->when($this->search, fn (Builder $q) => $q->where(function (Builder $q) {
                RecordSearch::orNumber($q, $this->search);

                // Deliveries have no reference of their own.
                $q->orWhereHas('order', fn (Builder $order) => $order->where(
                    fn (Builder $order) => RecordSearch::orLike($order, $order->getModel()->qualifyColumn('reference'), $this->search)
                ));
            }))
            ->when($this->user_id, fn (Builder $q) => $q->whereIn('user_owner_id', $this->user_id))
            ->when($this->label_id, fn (Builder $q) => $q->whereHas('labels', fn (Builder $q) => $q->whereIn(config('laravel-crm.db_table_prefix').'labels.id', $this->label_id)))
            ->orderBy(...$this->tableSort())
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($delivery = Delivery::find($id)) {
            $this->authorize('delete', $delivery);

            $delivery->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.delivery_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.deliveries.delivery-index', [
            'users' => $this->users,
            'labels' => $this->labels,
            'filterCount' => $this->filterCount(),
            'headers' => $this->headers(),
            'deliveries' => $this->deliveries(),
        ]);
    }
}
