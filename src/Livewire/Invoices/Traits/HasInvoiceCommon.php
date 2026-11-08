<?php

namespace VentureDrake\LaravelCrm\Livewire\Invoices\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Http\Rules\NotBeforeDate;
use VentureDrake\LaravelCrm\Livewire\Traits\HasLineItemQuantityRules;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPdfTemplate;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\InvoiceLine;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Services\InvoiceService;
use VentureDrake\LaravelCrm\Services\OrganizationService;
use VentureDrake\LaravelCrm\Services\PersonService;
use VentureDrake\LaravelCrm\Traits\HasCustomFormFields;

trait HasInvoiceCommon
{
    use HasCustomFormFields;
    use HasLineItemQuantityRules;
    use HasPdfTemplate;
    use Toast;

    protected InvoiceService $invoiceService;

    protected PersonService $personService;

    protected OrganizationService $organizationService;

    public $person_id;

    public $person_name;

    public $organization_id;

    public $organization_name;

    public $reference;

    public $currency;

    public $issue_date;

    public $due_date;

    public $terms;

    public $pipeline;

    public $pipeline_stage_id;

    public array $labels;

    public $user_owner_id;

    public array $products;

    public $sub_total = 0;

    public $discount = 0;

    public $tax = 0;

    public $adjustment = 0;

    public $total = 0;

    public $fromModelType = null;

    public $fromModelId = null;

    public $fromModel = null;

    protected function customFieldsModel(): string
    {
        return Invoice::class;
    }

    public function pdfTemplateDocType(): string
    {
        return 'invoice';
    }

    /**
     * An invoice raised from an order draws down the order line it was built
     * from, so the remaining quantity is recomputed against the invoices
     * already raised rather than trusted from the submitted row.
     */
    protected function lineItemDrawdown(): ?array
    {
        return [
            'model' => InvoiceLine::class,
            'relation' => 'invoice',
            'key' => 'invoice_line_id',
        ];
    }

    protected function rules()
    {
        return array_merge([
            'person_name' => 'required_without_all:organization_name,organization_id|max:255',
            'person_id' => 'required_without_all:organization_name,organization_id,person_name|max:255',
            'organization_name' => 'required_without_all:person_name,person_id|max:255',
            'organization_id' => 'required_without_all:person_name,person_id,organization_name|max:255',
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', new NotBeforeDate($this->issue_date, __('laravel-crm::lang.issue_date'))],
        ], $this->lineItemQuantityRules(), $this->pdfTemplateRules(), $this->customFieldRules());
    }

    protected function messages()
    {
        return array_merge([
            'person_name.required_without_all' => 'The contact person field is required if no organization.',
            'organization_name.required_without_all' => 'The organization field is required if no contact person.',
            'person_id.required_without_all' => 'The contact person field is required if no organization.',
            'organization_id.required_without_all' => 'The organization field is required of no contact person.',
        ], $this->lineItemQuantityMessages(), $this->customFieldMessages());
    }

    protected function validationAttributes()
    {
        return array_merge([
            'issue_date' => __('laravel-crm::lang.issue_date'),
            'due_date' => __('laravel-crm::lang.due_date'),
        ], $this->customFieldValidationAttributes());
    }

    public function boot(InvoiceService $invoiceService, PersonService $personService, OrganizationService $organizationService): void
    {
        $this->invoiceService = $invoiceService;
        $this->personService = $personService;
        $this->organizationService = $organizationService;
    }

    public function mountCommon($invoice = null)
    {
        $this->pipeline = Pipeline::where('model', get_class(new Invoice))->first();
        $this->mountPdfTemplate($invoice);
    }

    public function updateProducts($products, $sub_total = 0, $tax = 0, $total = 0): void
    {
        $this->products = $products;
        $this->sub_total = $sub_total;
        $this->tax = $tax;
        $this->total = $total;
    }
}
