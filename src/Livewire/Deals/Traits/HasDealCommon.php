<?php

namespace VentureDrake\LaravelCrm\Livewire\Deals\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\HasLineItemQuantityRules;
use VentureDrake\LaravelCrm\Livewire\Traits\HasVariationOptions;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Services\DealService;
use VentureDrake\LaravelCrm\Services\OrganizationService;
use VentureDrake\LaravelCrm\Services\PersonService;
use VentureDrake\LaravelCrm\Support\Quantity;
use VentureDrake\LaravelCrm\Traits\HasCustomFormFields;

trait HasDealCommon
{
    use HasCustomFormFields;
    use HasLineItemQuantityRules;
    use HasVariationOptions;
    use Toast;

    protected DealService $dealService;

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

    public $address_line_1;

    public $address_line_2;

    public $address_line_3;

    public $address_suburb;

    public $address_state;

    public $address_postcode;

    public $address_country = 'United States';

    public $title;

    public $description;

    public $amount;

    public $currency;

    public $expected_close = null;

    public $pipeline;

    public $pipeline_stage_id;

    public array $labels;

    public $user_owner_id;

    public array $products;

    public array $productOptions = [
        [
            'id' => null,
            'name' => null,
        ],
    ];

    protected function customFieldsModel(): string
    {
        return Deal::class;
    }

    protected function rules()
    {
        return array_merge([
            'person_name' => 'required_without_all:organization_name,organization_id|max:255',
            'person_id' => 'required_without_all:organization_name,organization_id,person_name|max:255',
            'organization_name' => 'required_without_all:person_name,person_id|max:255',
            'organization_id' => 'required_without_all:person_name,person_id,organization_name|max:255',
            'title' => 'required|max:255',
            'amount' => 'nullable|numeric',
        ], $this->lineItemQuantityRules(), $this->customFieldRules());
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
        return $this->customFieldValidationAttributes();
    }

    public function updatedAmount($value): void
    {
        $this->amount = str_replace(',', '', $value);
    }

    public function boot(DealService $dealService, PersonService $personService, OrganizationService $organizationService): void
    {
        $this->dealService = $dealService;
        $this->personService = $personService;
        $this->organizationService = $organizationService;
    }

    public function mountCommon()
    {
        foreach (Product::orderBy('name')->get() as $product) {
            $this->productOptions[] = [
                'id' => $product->id,
                'name' => $product->name,
            ];
        }
    }

    public function updatedProducts($value, $key)
    {
        $updating = explode('.', $key);

        // Picking a new product drops the old product's variation before the
        // row is repriced below.
        if (count($updating) > 1 && $updating[1] == 'id' && isset($this->products[$updating[0]])) {
            $this->products[$updating[0]]['product_variation_id'] = null;
        }

        // withTrashed, as in ModelProducts: a line on a since-deleted product
        // is still that product's line, not one to wipe as unresolved.
        foreach ($this->products as $index => $product) {
            if ($dealProduct = Product::withTrashed()->find($product['id'] ?? null)) {
                // Scoped to the row's product (and team); a variation of
                // anything else - or the blank placeholder - is dropped.
                $variation = $this->resolveRowVariation($dealProduct->id, $product['product_variation_id'] ?? null);
                $this->products[$index]['product_variation_id'] = $variation?->id;

                $price = $dealProduct->unitPriceFor($variation, $this->currency) ?? 0;
                $quantity = Quantity::toFloat($product['quantity'] ?? 1);
                $this->products[$index]['price'] = ($price / 100);
                $this->products[$index]['amount'] = ($price / 100) * $quantity;
            } elseif (($updating[1] ?? null) == 'id') {
                $this->products[$index]['product_variation_id'] = null;
                $this->products[$index]['price'] = null;
                $this->products[$index]['quantity'] = 1;
                $this->products[$index]['amount'] = null;
            }
        }
    }

    public function addProduct()
    {
        $this->products[] = [
            'id' => null,
            'product_variation_id' => null,
            'price' => null,
            'quantity' => 1,
            'amount' => null,
        ];
    }

    public function deleteProduct($index)
    {
        unset($this->products[$index]);
    }
}
