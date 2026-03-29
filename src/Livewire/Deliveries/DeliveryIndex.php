<?php

namespace VentureDrake\LaravelCrm\Livewire\Deliveries;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class DeliveryIndex extends Component
{
    use ClearsProperties, ResetsPaginationWhenPropsChanges, Toast, WithPagination;

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

    public function users(): Collection
    {
        return User::orderBy('name')->get();
    }

    public function labels(): Collection
    {
        return Label::all();
    }

    public function headers()
    {
        $headers = [
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->diffForHumans()],
            ['key' => 'delivery_id', 'label' => ucfirst(__('laravel-crm::lang.number'))],
            ['key' => 'reference', 'label' => ucfirst(__('laravel-crm::lang.reference'))],
        ];

        if (auth()->user()->can('view crm orders')) {
            $headers = array_merge($headers, [
                ['key' => 'order', 'label' => ucfirst(__('laravel-crm::lang.order')), 'disableLink' => true],
            ]);
        }

        $headers = array_merge($headers, [
            ['key' => 'person.name', 'label' => ucfirst(__('laravel-crm::lang.contact')), 'sortable' => false],
            ['key' => 'organization.name', 'label' => ucfirst(__('laravel-crm::lang.organization')), 'sortable' => false],
            /* ['key' => 'pipeline_stage', 'label' => ucfirst(__('laravel-crm::lang.stage'))], */
            ['key' => 'address', 'label' => ucfirst(__('laravel-crm::lang.shipping_address')), 'sortable' => false],
            ['key' => 'delivery_expected', 'label' => ucwords(__('laravel-crm::lang.delivery_expected')), 'format' => fn ($row, $field) => ($field) ? $field->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null],
            ['key' => 'delivered_on', 'label' => ucwords(__('laravel-crm::lang.delivered_on')), 'format' => fn ($row, $field) => ($field) ? $field->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null],
            ['key' => 'ownerUser.name', 'label' => 'Owner', 'format' => fn ($row, $field) => $field ?? ucfirst(__('laravel-crm::lang.unallocated')), 'sortable' => false],
        ]
        );

        return $headers;

        return [
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->diffForHumans()],
            /* ['key' => 'lead_id', 'label' => ucfirst(__('laravel-crm::lang.number'))],
            ['key' => 'title', 'label' => ucfirst(__('laravel-crm::lang.title'))],
            ['key' => 'labels', 'label' => ucfirst(__('laravel-crm::lang.labels')), 'format' => fn ($row, $field) => $field, 'sortable' => false],
            ['key' => 'amount', 'label' => ucfirst(__('laravel-crm::lang.value')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'delivery.name', 'label' => ucfirst(__('laravel-crm::lang.contact')), 'sortable' => false],
            ['key' => 'organization.name', 'label' => ucfirst(__('laravel-crm::lang.organization')), 'sortable' => false],
            ['key' => 'pipeline_stage', 'label' => ucfirst(__('laravel-crm::lang.stage')), 'sortable' => false],*/
            ['key' => 'ownerUser.name', 'label' => 'Owner', 'format' => fn ($row, $field) => $field ?? ucfirst(__('laravel-crm::lang.unallocated')), 'sortable' => false],
        ];
    }

    public function deliveries(): LengthAwarePaginator
    {
        return Delivery::when($this->user_id, fn (Builder $q) => $q->whereIn('user_owner_id', $this->user_id))
            ->when($this->label_id, fn (Builder $q) => $q->whereHas('labels', fn (Builder $q) => $q->whereIn('labels.id', $this->label_id)))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($delivery = Delivery::find($id)) {
            $delivery->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.delivery_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.deliveries.delivery-index', [
            'users' => $this->users(),
            'labels' => $this->labels(),
            'filterCount' => $this->filterCount(),
            'headers' => $this->headers(),
            'deliveries' => $this->deliveries(),
        ]);
    }
}
