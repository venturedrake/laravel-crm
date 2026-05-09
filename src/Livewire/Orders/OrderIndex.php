<?php

namespace VentureDrake\LaravelCrm\Livewire\Orders;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\SearchesEncryptableContacts;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class OrderIndex extends Component
{
    use ClearsProperties, ResetsPaginationWhenPropsChanges, SearchesEncryptableContacts, Toast, WithPagination;

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
        $this->pipeline = Pipeline::where('model', get_class(new Order))->first();
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
            ['key' => 'order_id', 'label' => ucfirst(__('laravel-crm::lang.number'))],
            ['key' => 'reference', 'label' => ucfirst(__('laravel-crm::lang.reference'))],
        ];

        if (auth()->user()->can('view crm quotes')) {
            $headers = array_merge($headers, [
                ['key' => 'quote', 'label' => ucfirst(__('laravel-crm::lang.quote')), 'disableLink' => true],
            ]);
        }

        $headers = array_merge($headers, [
            ['key' => 'labels', 'label' => ucfirst(__('laravel-crm::lang.labels')), 'format' => fn ($row, $field) => $field],
            ['key' => 'person.name', 'label' => ucfirst(__('laravel-crm::lang.contact')), 'sortable' => false],
            ['key' => 'organization.name', 'label' => ucfirst(__('laravel-crm::lang.organization')), 'sortable' => false],
            /* ['key' => 'pipeline_stage', 'label' => ucfirst(__('laravel-crm::lang.stage'))], */
            ['key' => 'subtotal', 'label' => ucfirst(__('laravel-crm::lang.sub_total')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'discount', 'label' => ucfirst(__('laravel-crm::lang.discount')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'tax', 'label' => ucfirst(__('laravel-crm::lang.tax')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'adjustments', 'label' => ucfirst(__('laravel-crm::lang.adjustment')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'total', 'label' => ucfirst(__('laravel-crm::lang.total')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'ownerUser.name', 'label' => 'Owner', 'format' => fn ($row, $field) => $field ?? ucfirst(__('laravel-crm::lang.unallocated')), 'sortable' => false],
        ]
        );

        return $headers;
    }

    public function orders(): LengthAwarePaginator
    {
        return Order::select(
            config('laravel-crm.db_table_prefix').'orders.*',
            config('laravel-crm.db_table_prefix').'people.first_name',
            config('laravel-crm.db_table_prefix').'people.last_name',
            config('laravel-crm.db_table_prefix').'organizations.name'
        )
            ->leftJoin(config('laravel-crm.db_table_prefix').'people', config('laravel-crm.db_table_prefix').'orders.person_id', '=', config('laravel-crm.db_table_prefix').'people.id')
            ->leftJoin(config('laravel-crm.db_table_prefix').'organizations', config('laravel-crm.db_table_prefix').'orders.organization_id', '=', config('laravel-crm.db_table_prefix').'organizations.id')
            ->when($this->search, function (Builder $q) {
                $prefix = config('laravel-crm.db_table_prefix');
                $term = $this->search;

                $q->where(function ($q) use ($prefix, $term) {
                    if ($this->encryptionEnabled()) {
                        $personIds = $this->matchingPersonIds($term);
                        $organizationIds = $this->matchingOrganizationIds($term);

                        // Force an empty result when neither matches.
                        $q->whereIn($prefix.'orders.person_id', $personIds->isEmpty() ? [0] : $personIds)
                            ->orWhereIn($prefix.'orders.organization_id', $organizationIds->isEmpty() ? [0] : $organizationIds);
                    } else {
                        $q->orWhere($prefix.'organizations.name', 'like', "%$term%")
                            ->orWhere($prefix.'people.first_name', 'like', "%$term%")
                            ->orWhere($prefix.'people.last_name', 'like', "%$term%")
                            ->orWhereRaw('CONCAT('.$prefix."people.first_name, ' ', ".$prefix.'people.last_name) like ?', ["%$term%"]);
                    }
                });
            })->when($this->user_id, fn (Builder $q) => $q->whereIn('user_owner_id', $this->user_id))
            ->when($this->label_id, fn (Builder $q) => $q->whereHas('labels', fn (Builder $q) => $q->whereIn('labels.id', $this->label_id)))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($order = Order::find($id)) {
            $order->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.order_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.orders.order-index', [
            'users' => $this->users(),
            'labels' => $this->labels(),
            'filterCount' => $this->filterCount(),
            'headers' => $this->headers(),
            'orders' => $this->orders(),
        ]);
    }
}
