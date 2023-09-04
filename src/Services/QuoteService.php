<?php

namespace VentureDrake\LaravelCrm\Services;

use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\QuoteProduct;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Repositories\QuoteRepository;

class QuoteService
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * LeadService constructor.
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(QuoteRepository $quoteRepository)
    {
        $this->quoteRepository = $quoteRepository;
    }

    public function create($request, $person = null, $organisation = null, $client = null)
    {
        $quote = Quote::create([
            'lead_id' => $request->lead_id ?? null,
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
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
            'user_owner_id' => $request->user_owner_id,
        ]);

        $quote->labels()->sync($request->labels ?? []);

        if (isset($request->products)) {
            foreach ($request->products as $product) {
                if(isset($product['product_id']) && $product['quantity'] > 0) {
                    if(! Product::find($product['product_id'])) {
                        $newProduct = $this->addProduct($product, $request);
                        $product['product_id'] = $newProduct->id;
                    }
                }

                if (isset($product['product_id']) && $product['product_id'] > 0 && $product['quantity'] > 0) {
                    if($productForTax = Product::find($product['product_id'])) {
                        if($productForTax->taxRate) {
                            $taxRate = $productForTax->taxRate->rate;
                        } elseif($productForTax->tax_rate) {
                            $taxRate = $productForTax->tax_rate;
                        } else {
                            $taxRate = Setting::where('name', 'tax_rate')->first()->value ?? 0;
                        }
                    }

                    $quote->quoteProducts()->create([
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                        'price' => $product['unit_price'],
                        'amount' => $product['amount'],
                        'tax_rate' => $taxRate ?? 0,
                        'tax_amount' => ($product['amount'] * 100) * ($taxRate / 100),
                        'currency' => $request->currency,
                        'comments' => $product['comments'],
                    ]);
                }
            }
        }

        return $quote;
    }

    public function update($request, Quote $quote, $person = null, $organisation = null, $client = null)
    {
        $quote->update([
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
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
            'user_owner_id' => $request->user_owner_id,
        ]);

        $quote->labels()->sync($request->labels ?? []);

        if (isset($request->products)) {
            $quoteProductIds = [];

            foreach ($request->products as $product) {
                if (isset($product['quote_product_id']) && $quoteProduct = QuoteProduct::find($product['quote_product_id'])) {
                    if (! isset($product['product_id']) || $product['quantity'] == 0) {
                        $quoteProduct->delete();
                    } else {
                        if(! Product::find($product['product_id'])) {
                            $newProduct = $this->addProduct($product, $request);
                            $product['product_id'] = $newProduct->id;
                        }

                        if (isset($product['product_id']) && $product['product_id'] > 0 && $product['quantity'] > 0) {
                            if($productForTax = Product::find($product['product_id'])) {
                                if($productForTax->taxRate) {
                                    $taxRate = $productForTax->taxRate->rate;
                                } elseif($productForTax->tax_rate) {
                                    $taxRate = $productForTax->tax_rate;
                                } else {
                                    $taxRate = Setting::where('name', 'tax_rate')->first()->value ?? 0;
                                }
                            }

                            $quoteProduct->update([
                                'product_id' => $product['product_id'],
                                'quantity' => $product['quantity'],
                                'price' => $product['unit_price'],
                                'amount' => $product['amount'],
                                'tax_rate' => $taxRate ?? 0,
                                'tax_amount' => ($product['amount'] * 100) * ($taxRate / 100),
                                'currency' => $request->currency,
                                'comments' => $product['comments'],
                            ]);

                            $quoteProductIds[] = $quoteProduct->id;
                        }
                    }
                } elseif(isset($product['product_id']) && $product['quantity'] > 0) {
                    if(! Product::find($product['product_id'])) {
                        $newProduct = $this->addProduct($product, $request);
                        $product['product_id'] = $newProduct->id;
                    }

                    if (isset($product['product_id']) && $product['product_id'] > 0 && $product['quantity'] > 0) {
                        if($productForTax = Product::find($product['product_id'])) {
                            if($productForTax->taxRate) {
                                $taxRate = $productForTax->taxRate->rate;
                            } elseif($productForTax->tax_rate) {
                                $taxRate = $productForTax->tax_rate;
                            } else {
                                $taxRate = Setting::where('name', 'tax_rate')->first()->value ?? 0;
                            }
                        }

                        $quoteProduct = $quote->quoteProducts()->create([
                            'product_id' => $product['product_id'],
                            'quantity' => $product['quantity'],
                            'price' => $product['unit_price'],
                            'amount' => $product['amount'],
                            'tax_rate' => $taxRate ?? 0,
                            'tax_amount' => ($product['amount'] * 100) * ($taxRate / 100),
                            'currency' => $request->currency,
                            'comments' => $product['comments'],
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

    protected function addProduct($product, $request)
    {
        $newProduct = Product::create([
            'name' => $product['product_id'],
            'tax_rate' => Setting::where('name', 'tax_rate')->first()->value ?? null,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $newProduct->productPrices()->create([
            'unit_price' => $product['unit_price'],
            'currency' => $request->currency,
        ]);

        return $newProduct;
    }
}
