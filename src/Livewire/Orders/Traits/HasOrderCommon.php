<?php

namespace VentureDrake\LaravelCrm\Livewire\Orders\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\HasLineItemQuantityRules;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPdfTemplate;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Services\OrderService;
use VentureDrake\LaravelCrm\Services\OrganizationService;
use VentureDrake\LaravelCrm\Services\PersonService;
use VentureDrake\LaravelCrm\Support\FormOptions;
use VentureDrake\LaravelCrm\Traits\HasCustomFormFields;

trait HasOrderCommon
{
    use HasCustomFormFields;
    use HasLineItemQuantityRules;
    use HasPdfTemplate;
    use Toast;

    protected OrderService $orderService;

    protected PersonService $personService;

    protected OrganizationService $organizationService;

    public $person_id;

    public $person_name;

    public $phone;

    public $phone_type = 'mobile';

    public $email;

    public $email_type;

    public $organization_id;

    public $organization_name;

    public $countries;

    public array $addresses = [
        'billing' => [
            'id' => null,
            'address_type_id',
            'contact' => null,
            'phone' => null,
            'line1' => null,
            'line2' => null,
            'line3' => null,
            'city' => null,
            'state' => null,
            'code' => null,
            'country' => null,
            'primary' => 1,
        ],
        'shipping' => [
            'id' => null,
            'address_type_id',
            'contact' => null,
            'phone' => null,
            'line1' => null,
            'line2' => null,
            'line3' => null,
            'city' => null,
            'state' => null,
            'code' => null,
            'country' => null,
            'primary' => 1,
        ],
    ];

    public $description;

    public $amount;

    public $reference;

    public $currency;

    public $pipeline;

    public $pipeline_stage_id;

    public array $labels = [];

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

    public $selectedAddressTab = 'billing';

    protected function customFieldsModel(): string
    {
        return Order::class;
    }

    public function pdfTemplateDocType(): string
    {
        return 'order';
    }

    /**
     * The install-wide order form toggles (config laravel-crm.orders.form).
     */
    protected function orderForm(): FormOptions
    {
        return FormOptions::for('orders');
    }

    protected function rules()
    {
        $form = $this->orderForm();

        return array_merge([
            'person_name' => 'required_without_all:organization_name,organization_id|max:255',
            'person_id' => 'required_without_all:organization_name,organization_id,person_name|max:255',
            'organization_name' => 'required_without_all:person_name,person_id|max:255',
            'organization_id' => 'required_without_all:person_name,person_id,organization_name|max:255',
            'amount' => 'nullable|numeric',
        ],
            $form->allowsUnallocatedOwner() ? [] : ['user_owner_id' => 'required|not_in:0'],
            $this->lineItemQuantityRules(),
            $this->pdfTemplateRules(),
            $form->shows('custom_fields') ? $this->customFieldRules() : []
        );
    }

    protected function messages()
    {
        return array_merge([
            'person_name.required_without_all' => 'The contact person field is required if no organization.',
            'organization_name.required_without_all' => 'The organization field is required if no contact person.',
            'person_id.required_without_all' => 'The contact person field is required if no organization.',
            'organization_id.required_without_all' => 'The organization field is required of no contact person.',
        ], $this->lineItemQuantityMessages(), $this->orderForm()->shows('custom_fields') ? $this->customFieldMessages() : []);
    }

    protected function validationAttributes()
    {
        $form = $this->orderForm();

        return array_merge(
            $form->allowsUnallocatedOwner() ? [] : ['user_owner_id' => __('laravel-crm::lang.owner')],
            $form->shows('custom_fields') ? $this->customFieldValidationAttributes() : []
        );
    }

    /**
     * The address rows to save: only the configured types, in the form's
     * own order. An address of a type left out is never sent, so it is
     * neither validated nor touched by the service.
     */
    protected function addressesToSave(): array
    {
        return array_intersect_key($this->addresses, array_flip($this->orderForm()->addressTypes()));
    }

    /**
     * The view data the shared order form reads.
     */
    protected function orderFormViewData(): array
    {
        $form = $this->orderForm();

        return [
            'orderForm' => $form,
            'addressTypes' => $form->addressTypes(),
        ];
    }

    public function boot(OrderService $orderService, PersonService $personService, OrganizationService $organizationService): void
    {
        $this->orderService = $orderService;
        $this->personService = $personService;
        $this->organizationService = $organizationService;
    }

    public function mountCommon($order = null)
    {
        $this->countries = \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\countries();
        $this->pipeline = Pipeline::where('model', get_class(new Order))->first();
        $this->mountPdfTemplate($order);

        if (! $this->orderForm()->showsAddressType($this->selectedAddressTab)) {
            $this->selectedAddressTab = $this->orderForm()->addressTypes()[0] ?? $this->selectedAddressTab;
        }
    }

    public function updateProducts($products, $sub_total = 0, $tax = 0, $total = 0): void
    {
        $this->products = $products;
        $this->sub_total = $sub_total;
        $this->tax = $tax;
        $this->total = $total;
    }
}
