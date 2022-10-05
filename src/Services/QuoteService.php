<?php

namespace VentureDrake\LaravelCrm\Services;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\QuoteProduct;
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

    public function create($request, $person = null, $organisation = null)
    {
        $quote = Quote::create([
            'external_id' => Uuid::uuid4()->toString(),
            'lead_id' => $request->lead_id ?? null,
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'expected_close' => $request->expected_close,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $quote->labels()->sync($request->labels ?? []);

        if (isset($request->item_quote_product_id)) {
            foreach ($request->item_quote_product_id as $quoteProductKey => $quoteProductValue) {
                $quote->quoteProducts()->create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'product_id' => $request->item_product_id[$quoteProductKey],
                    'price' => $request->item_price[$quoteProductKey],
                    'quantity' => $request->item_quantity[$quoteProductKey],
                    'amount' => $request->item_amount[$quoteProductKey],
                ]);
            }
        }
        
        return $quote;
    }

    public function update($request, Quote $quote, $person = null, $organisation = null)
    {
        $quote->update([
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'expected_close' => $request->expected_close,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $quote->labels()->sync($request->labels ?? []);
        
        if (isset($request->item_quote_product_id)) {
            foreach ($request->item_quote_product_id as $quoteProductKey => $quoteProductValue) {
                $quoteProduct = QuoteProduct::find($quoteProductValue);
                
                if ($quoteProduct) {
                    $quoteProduct->update([
                        'product_id' => $request->item_product_id[$quoteProductKey],
                        'price' => $request->item_price[$quoteProductKey],
                        'quantity' => $request->item_quantity[$quoteProductKey],
                        'amount' => $request->item_amount[$quoteProductKey],
                    ]);
                }
            }
        }
        
        return $quote;
    }
}
