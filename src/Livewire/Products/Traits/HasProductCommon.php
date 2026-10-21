<?php

namespace VentureDrake\LaravelCrm\Livewire\Products\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Services\ProductService;
use VentureDrake\LaravelCrm\Traits\HasCustomFormFields;

trait HasProductCommon
{
    use HasCustomFormFields;
    use Toast;

    protected ProductService $productService;

    public $name;

    public $code;

    public $barcode;

    public array $productCategories = [
        ['id' => null, 'name' => null],
    ];

    public $product_category;

    public $purchase_account;

    public $sales_account;

    public $description;

    public $unit;

    public $unit_price;

    public array $taxRates = [
        ['id' => null, 'name' => null],
    ];

    public $tax_rate_id;

    public $tax_rate;

    public $currency;

    public $user_owner_id;

    /**
     * Variation rows edited in the product form, in display order. Each row is
     * ['id' => ?int, 'name', 'code', 'barcode', 'unit_price', 'active'];
     * ProductService::syncVariations() persists them on save.
     */
    public array $variations = [];

    protected function customFieldsModel(): string
    {
        return Product::class;
    }

    protected function rules()
    {
        return array_merge([
            'name' => 'required|max:255',
            'variations' => 'array',
            'variations.*.name' => 'required|max:255',
            'variations.*.code' => 'nullable|max:255',
            'variations.*.barcode' => 'nullable|max:255',
            'variations.*.unit_price' => 'nullable|numeric|min:0',
            'variations.*.active' => 'boolean',
        ], $this->customFieldRules());
    }

    protected function messages()
    {
        return $this->customFieldMessages();
    }

    protected function validationAttributes()
    {
        return array_merge([
            'variations.*.name' => ucfirst(__('laravel-crm::lang.name')),
            'variations.*.code' => strtoupper(__('laravel-crm::lang.sku')),
            'variations.*.barcode' => ucfirst(__('laravel-crm::lang.barcode')),
            'variations.*.unit_price' => ucfirst(__('laravel-crm::lang.price')),
            'variations.*.active' => ucfirst(__('laravel-crm::lang.active')),
        ], $this->customFieldValidationAttributes());
    }

    /**
     * The variation price inputs use the same $money mask as the product's own
     * unit price, so they arrive as "1,234.50". Strip the formatting for the
     * numeric check only; the stored value is normalised the same way by
     * ProductPrice::setUnitPriceAttribute() (Money::toInteger) on save.
     */
    protected function prepareForValidation($attributes)
    {
        foreach ($attributes['variations'] ?? [] as $index => $row) {
            $price = $row['unit_price'] ?? null;

            if (! is_string($price)) {
                continue;
            }

            $cleaned = preg_replace('/[^0-9.\-]/', '', $price);

            // Leave unparseable input alone so `numeric` rejects it.
            $attributes['variations'][$index]['unit_price'] = trim($price) === ''
                ? null
                : ($cleaned === '' ? $price : $cleaned);
        }

        return $attributes;
    }

    public function boot(ProductService $productService): void
    {
        $this->productService = $productService;
    }

    public function mountCommon()
    {
        foreach (ProductCategory::all() as $productCategory) {
            $this->productCategories[] = [
                'id' => $productCategory->id,
                'name' => $productCategory->name,
            ];
        }

        foreach (TaxRate::get() as $taxRate) {
            $this->taxRates[] = [
                'id' => $taxRate->id,
                'name' => $taxRate->name,
                'rate' => $taxRate->rate,
            ];
        }
    }

    public function addVariation(): void
    {
        $this->variations[] = [
            'id' => null,
            'name' => '',
            'code' => null,
            'barcode' => null,
            'unit_price' => null,
            'active' => true,
        ];
    }

    public function removeVariation($index): void
    {
        $variations = array_values($this->variations);

        if (! array_key_exists((int) $index, $variations)) {
            return;
        }

        unset($variations[(int) $index]);

        $this->variations = array_values($variations);
    }

    public function moveVariationUp($index): void
    {
        $this->swapVariations((int) $index, (int) $index - 1);
    }

    public function moveVariationDown($index): void
    {
        $this->swapVariations((int) $index, (int) $index + 1);
    }

    protected function swapVariations(int $from, int $to): void
    {
        $variations = array_values($this->variations);

        if (! array_key_exists($from, $variations) || ! array_key_exists($to, $variations)) {
            return;
        }

        [$variations[$from], $variations[$to]] = [$variations[$to], $variations[$from]];

        $this->variations = $variations;
    }

    /**
     * Form rows for a product's variations, each priced in $currency (major
     * units), or null when the variation has no own price in that currency.
     */
    protected function variationRows(Product $product, ?string $currency): array
    {
        return $product->productVariations()
            ->with('productPrices')
            ->get()
            ->map(function ($variation) use ($currency) {
                $price = $variation->priceFor($currency);

                return [
                    'id' => $variation->id,
                    'name' => $variation->name,
                    'code' => $variation->code,
                    'barcode' => $variation->barcode,
                    'unit_price' => isset($price->unit_price) ? $price->unit_price / 100 : null,
                    'active' => (bool) $variation->active,
                ];
            })
            ->all();
    }

    public function updatedTaxRateId($value)
    {
        if ($value) {
            $this->tax_rate = TaxRate::find($value)->rate;
        } else {
            $this->tax_rate = number_format(app('laravel-crm.settings')->get('tax_rate', 0), 2);
        }
    }
}
