<?php

namespace VentureDrake\LaravelCrm\Services;

use VentureDrake\LaravelCrm\Models\DealProduct;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\QuoteProduct;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Repositories\QuoteRepository;
use VentureDrake\LaravelCrm\Support\Money;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;
use VentureDrake\LaravelCrm\Support\Quantity;
use VentureDrake\LaravelCrm\Traits\ResolvesProductVariation;

class QuoteService
{
    use ResolvesProductVariation;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * LeadService constructor.
     */
    public function __construct(QuoteRepository $quoteRepository)
    {
        $this->quoteRepository = $quoteRepository;
    }

    public function create($request, $person = null, $organization = null, $client = null)
    {
        $quote = Quote::create([
            'lead_id' => $request->lead_id ?? null,
            'person_id' => $person->id ?? null,
            'organization_id' => $organization->id ?? null,
            'client_id' => $client->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'reference' => $request->reference,
            'currency' => $request->currency,
            'issue_at' => $request->issue_at,
            'expire_at' => $request->expire_at,
            'terms' => $request->terms,
            'subtotal' => $request->sub_total,
            'discount' => $request->discount,
            'tax' => $request->tax,
            'adjustments' => $request->adjustment,
            'total' => $request->total,
            'pdf_template' => PdfTemplateRegistry::sanitize($request->pdf_template ?? null),
            'user_owner_id' => $request->user_owner_id,
            'pipeline_id' => optional(PipelineStage::find($request->pipeline_stage_id))->pipeline?->id,
            'pipeline_stage_id' => $request->pipeline_stage_id ?? null,
        ]);

        $quote->labels()->sync($request->labels ?? []);

        if (isset($request->products)) {
            $quoteProductOrder = 0;

            foreach ($request->products as $product) {
                $quoteProductOrder++;

                if (isset($product['id']) && Quantity::isPositive($product['quantity'])) {
                    if (! Product::withTrashed()->find($product['id'])) {
                        $newProduct = $this->addProduct($product, $request);
                        $product['id'] = $newProduct->id;
                    }
                }

                if (isset($product['id']) && $product['id'] > 0 && Quantity::isPositive($product['quantity'])) {
                    $taxRate = $this->resolveTaxRate($product['id']);

                    $quote->quoteProducts()->create([
                        'product_id' => $product['id'],
                        'product_variation_id' => $this->variationIdFor($product['id'], $product['product_variation_id'] ?? null, $this->sourceVariationId(DealProduct::class, $product['deal_product_id'] ?? null)),
                        'quantity' => $product['quantity'],
                        'price' => $product['unit_price'],
                        'amount' => $product['amount'],
                        'tax_rate' => $taxRate,
                        'tax_amount' => (Money::toFloat($product['amount']) * 100) * ($taxRate / 100),
                        'currency' => $request->currency,
                        'comments' => $product['comments'],
                        'order' => $quoteProductOrder,
                    ]);
                }
            }
        }

        return $quote;
    }

    public function update($request, Quote $quote, $person = null, $organization = null, $client = null)
    {
        $quote->update([
            'person_id' => $person->id ?? null,
            'organization_id' => $organization->id ?? null,
            'client_id' => $client->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'reference' => $request->reference,
            'currency' => $request->currency,
            'issue_at' => $request->issue_at,
            'expire_at' => $request->expire_at,
            'terms' => $request->terms,
            'subtotal' => $request->sub_total,
            'discount' => $request->discount,
            'tax' => $request->tax,
            'adjustments' => $request->adjustment,
            'total' => $request->total,
            'pdf_template' => PdfTemplateRegistry::resolveUpdate($request->pdf_template ?? null, $quote->pdf_template),
            'user_owner_id' => $request->user_owner_id,
            'pipeline_id' => optional(PipelineStage::find($request->pipeline_stage_id))->pipeline?->id,
            'pipeline_stage_id' => $request->pipeline_stage_id ?? null,
        ]);

        $quote->labels()->sync($request->labels ?? []);

        if (isset($request->products)) {
            $quoteProductIds = [];
            $quoteProductOrder = 0;

            foreach ($request->products as $product) {
                $quoteProductOrder++;

                if (isset($product['quote_product_id']) && $quoteProduct = QuoteProduct::find($product['quote_product_id'])) {
                    if (! isset($product['id']) || Quantity::isZero($product['quantity'])) {
                        $quoteProduct->delete();
                    } else {
                        if (! Product::withTrashed()->find($product['id'])) {
                            $newProduct = $this->addProduct($product, $request);
                            $product['id'] = $newProduct->id;
                        }

                        if (isset($product['id']) && $product['id'] > 0 && Quantity::isPositive($product['quantity'])) {
                            $taxRate = $this->resolveTaxRate($product['id']);

                            $quoteProduct->update([
                                'product_id' => $product['id'],
                                'product_variation_id' => $this->variationIdFor($product['id'], $product['product_variation_id'] ?? null, $quoteProduct->product_variation_id),
                                'quantity' => $product['quantity'],
                                'price' => $product['unit_price'],
                                'amount' => $product['amount'],
                                'tax_rate' => $taxRate,
                                'tax_amount' => (Money::toFloat($product['amount']) * 100) * ($taxRate / 100),
                                'currency' => $request->currency,
                                'comments' => $product['comments'],
                                'order' => $quoteProductOrder,
                            ]);

                            $quoteProductIds[] = $quoteProduct->id;
                        }
                    }
                } elseif (isset($product['id']) && Quantity::isPositive($product['quantity'])) {
                    if (! Product::withTrashed()->find($product['id'])) {
                        $newProduct = $this->addProduct($product, $request);
                        $product['id'] = $newProduct->id;
                    }

                    if (isset($product['id']) && $product['id'] > 0 && Quantity::isPositive($product['quantity'])) {
                        $taxRate = $this->resolveTaxRate($product['id']);

                        $quoteProduct = $quote->quoteProducts()->create([
                            'product_id' => $product['id'],
                            'product_variation_id' => $this->variationIdFor($product['id'], $product['product_variation_id'] ?? null),
                            'quantity' => $product['quantity'],
                            'price' => $product['unit_price'],
                            'amount' => $product['amount'],
                            'tax_rate' => $taxRate,
                            'tax_amount' => (Money::toFloat($product['amount']) * 100) * ($taxRate / 100),
                            'currency' => $request->currency,
                            'comments' => $product['comments'],
                            'order' => $quoteProductOrder,
                        ]);

                        $quoteProductIds[] = $quoteProduct->id;
                    }
                }
            }

            foreach ($quote->quoteProducts as $quoteProduct) {
                if (! in_array($quoteProduct->id, $quoteProductIds)) {
                    $quoteProduct->delete();
                }
            }
        }

        return $quote;
    }

    /**
     * Resolve the applicable tax rate for a product, falling back through
     * product → default tax rate → settings → 0. Always returns a numeric value.
     */
    protected function resolveTaxRate($productId): float
    {
        if ($product = Product::withTrashed()->find($productId)) {
            if ($product->taxRate) {
                return (float) $product->taxRate->rate;
            }

            if ($product->tax_rate) {
                return (float) $product->tax_rate;
            }
        }

        if ($default = TaxRate::where('default', 1)->first()) {
            return (float) $default->rate;
        }

        return (float) (optional(Setting::where('name', 'tax_rate')->first())->value ?? 0);
    }

    protected function addProduct($product, $request)
    {
        $newProduct = Product::create([
            'name' => $product['product_id'],
            'user_owner_id' => $request->user_owner_id,
        ]);

        $newProduct->productPrices()->create([
            'unit_price' => $product['unit_price'],
            'currency' => $request->currency,
        ]);

        return $newProduct;
    }
}
