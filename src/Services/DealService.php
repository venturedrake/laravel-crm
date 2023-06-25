<?php

namespace VentureDrake\LaravelCrm\Services;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\DealProduct;
use VentureDrake\LaravelCrm\Repositories\DealRepository;

class DealService
{
    /**
     * @var DealRepository
     */
    private $dealRepository;

    /**
     * LeadService constructor.
     * @param DealRepository $dealRepository
     */
    public function __construct(DealRepository $dealRepository)
    {
        $this->dealRepository = $dealRepository;
    }

    public function create($request, $person = null, $organisation = null, $client = null)
    {
        $deal = Deal::create([
            'external_id' => Uuid::uuid4()->toString(),
            'lead_id' => $request->lead_id ?? null,
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'client_id' => $client->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'expected_close' => $request->expected_close,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $deal->labels()->sync($request->labels ?? []);

        if (isset($request->item_deal_product_id)) {
            foreach ($request->item_deal_product_id as $dealProductKey => $dealProductValue) {
                $deal->dealProducts()->create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'product_id' => $request->item_product_id[$dealProductKey],
                    'price' => $request->item_price[$dealProductKey],
                    'quantity' => $request->item_quantity[$dealProductKey],
                    'amount' => $request->item_amount[$dealProductKey],
                ]);
            }
        }

        return $deal;
    }

    public function update($request, Deal $deal, $person = null, $organisation = null, $client = null)
    {
        $deal->update([
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'client_id' => $client->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'expected_close' => $request->expected_close,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $deal->labels()->sync($request->labels ?? []);

        if (isset($request->item_deal_product_id)) {
            foreach ($request->item_deal_product_id as $dealProductKey => $dealProductValue) {
                $dealProduct = DealProduct::find($dealProductValue);

                if ($dealProduct) {
                    $dealProduct->update([
                        'product_id' => $request->item_product_id[$dealProductKey],
                        'price' => $request->item_price[$dealProductKey],
                        'quantity' => $request->item_quantity[$dealProductKey],
                        'amount' => $request->item_amount[$dealProductKey],
                    ]);
                }
            }
        }

        return $deal;
    }
}
