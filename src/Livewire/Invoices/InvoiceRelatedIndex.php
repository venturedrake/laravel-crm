<?php

namespace VentureDrake\LaravelCrm\Livewire\Invoices;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Pipeline;

class InvoiceRelatedIndex extends Component
{
    use Toast;

    public Model $model;

    public ?Pipeline $pipeline = null;

    public $timezone;

    public function mount(Model $model)
    {
        $this->model = $model;
        $this->pipeline = Pipeline::where('model', get_class(new Invoice))->first();
        $this->timezone = app('laravel-crm.settings')->get('timezone', 'UTC');
    }

    public function headers(): array
    {
        return [
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->diffForHumans()],
            ['key' => 'invoice_id', 'label' => ucfirst(__('laravel-crm::lang.number'))],
            ['key' => 'reference', 'label' => ucfirst(__('laravel-crm::lang.reference'))],
            ['key' => 'issue_date', 'label' => ucwords(__('laravel-crm::lang.date')), 'format' => fn ($row, $field) => $field?->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d'))],
            ['key' => 'due_date', 'label' => ucwords(__('laravel-crm::lang.due_date')), 'format' => fn ($row, $field) => $field?->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d'))],
            ['key' => 'total', 'label' => ucfirst(__('laravel-crm::lang.total')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'fully_paid_at', 'label' => ucwords(__('laravel-crm::lang.paid_date')), 'format' => fn ($row, $field) => $field?->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d'))],
            ['key' => 'amount_paid', 'label' => ucfirst(__('laravel-crm::lang.paid')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'amount_due', 'label' => ucfirst(__('laravel-crm::lang.due')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'overdue_by', 'label' => ucfirst(__('laravel-crm::lang.overdue_by')), 'sortable' => false],
            ['key' => 'sent', 'label' => ucfirst(__('laravel-crm::lang.sent'))],
        ];
    }

    #[Computed]
    public function invoices(): Collection
    {
        return $this->model->invoices()->latest()->get();
    }

    public function delete($id): void
    {
        if ($invoice = Invoice::find($id)) {
            $invoice->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.invoice_deleted')));
            $this->dispatch('$refresh');
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.invoices.invoice-related-index', [
            'headers' => $this->headers(),
        ]);
    }
}
