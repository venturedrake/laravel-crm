<?php

namespace VentureDrake\LaravelCrm\Livewire\Invoices;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class InvoiceIndex extends Component
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

    public $timezone;

    public function mount()
    {
        $this->pipeline = Pipeline::where('model', get_class(new Invoice))->first();
        $this->timezone = app('laravel-crm.settings')->get('timezone', 'UTC');
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
            ['key' => 'invoice_id', 'label' => ucfirst(__('laravel-crm::lang.number'))],
            ['key' => 'reference', 'label' => ucfirst(__('laravel-crm::lang.reference'))],
        ];

        if (auth()->user()->can('view crm orders')) {
            $headers = array_merge($headers, [
                ['key' => 'order', 'label' => ucfirst(__('laravel-crm::lang.order'))],
            ]);
        }

        $headers = array_merge($headers, [
            ['key' => 'person.name', 'label' => ucfirst(__('laravel-crm::lang.contact')), 'sortable' => false],
            ['key' => 'organization.name', 'label' => ucfirst(__('laravel-crm::lang.organization')), 'sortable' => false],
            ['key' => 'issue_date', 'label' => ucwords(__('laravel-crm::lang.date')), 'format' => fn ($row, $field) => ($field) ? $field->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null],
            ['key' => 'due_date', 'label' => ucwords(__('laravel-crm::lang.due_date')), 'format' => fn ($row, $field) => ($field) ? $field->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null],
            ['key' => 'total', 'label' => ucfirst(__('laravel-crm::lang.total')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'fully_paid_at', 'label' => ucwords(__('laravel-crm::lang.paid_date')), 'format' => fn ($row, $field) => ($field) ? $field->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null],
            ['key' => 'amount_paid', 'label' => ucfirst(__('laravel-crm::lang.paid')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'amount_due', 'label' => ucfirst(__('laravel-crm::lang.due')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'overdue_by', 'label' => ucfirst(__('laravel-crm::lang.overdue_by')), 'sortable' => false],
            ['key' => 'sent', 'label' => ucfirst(__('laravel-crm::lang.sent'))],
        ]
        );

        return $headers;
    }

    public function invoices(): LengthAwarePaginator
    {
        return Invoice::select(
            config('laravel-crm.db_table_prefix').'invoices.*',
            config('laravel-crm.db_table_prefix').'people.first_name',
            config('laravel-crm.db_table_prefix').'people.last_name',
            config('laravel-crm.db_table_prefix').'organizations.name'
        )
            ->leftJoin(config('laravel-crm.db_table_prefix').'people', config('laravel-crm.db_table_prefix').'invoices.person_id', '=', config('laravel-crm.db_table_prefix').'people.id')
            ->leftJoin(config('laravel-crm.db_table_prefix').'organizations', config('laravel-crm.db_table_prefix').'invoices.organization_id', '=', config('laravel-crm.db_table_prefix').'organizations.id')
            ->when($this->search, function (Builder $q) {
                $q->where(function ($q) {
                    $q->orWhere(config('laravel-crm.db_table_prefix').'organizations.name', 'like', "%$this->search%")
                        ->orWhere(config('laravel-crm.db_table_prefix').'people.first_name', 'like', "%$this->search%")
                        ->orWhere(config('laravel-crm.db_table_prefix').'people.last_name', 'like', "%$this->search%")
                        ->orWhereRaw('CONCAT('.config('laravel-crm.db_table_prefix')."people.first_name, ' ', ".config('laravel-crm.db_table_prefix').'people.last_name) like ?', ["%$this->search%"]);
                });
            })
            ->when($this->user_id, fn (Builder $q) => $q->whereIn('user_owner_id', $this->user_id))
            ->when($this->label_id, fn (Builder $q) => $q->whereHas('labels', fn (Builder $q) => $q->whereIn('labels.id', $this->label_id)))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($invoice = Invoice::find($id)) {
            $invoice->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.invoice_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.invoices.invoice-index', [
            'users' => $this->users(),
            'labels' => $this->labels(),
            'filterCount' => $this->filterCount(),
            'headers' => $this->headers(),
            'invoices' => $this->invoices(),
        ]);
    }
}
