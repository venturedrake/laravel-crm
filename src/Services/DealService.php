<?php

namespace VentureDrake\LaravelCrm\Services;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Repositories\DealRepository;

class DealService
{
    /**
     * @var DealRepository
     */
    private $dealRepository;

    /**
     * LeadService constructor.
     */
    public function __construct(DealRepository $dealRepository)
    {
        $this->dealRepository = $dealRepository;
    }

    public function create($request, $person = null, $organization = null, $client = null)
    {
        $deal = Deal::create([
            'external_id' => Uuid::uuid4()->toString(),
            'lead_id' => $request->lead_id ?? null,
            'person_id' => $person->id ?? null,
            'organization_id' => $organization->id ?? null,
            'client_id' => $client->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'expected_close' => $request->expected_close,
            'user_owner_id' => $request->user_owner_id,
            'pipeline_id' => optional(PipelineStage::find($request->pipeline_stage_id))->pipeline?->id,
            'pipeline_stage_id' => $request->pipeline_stage_id ?? null,
        ]);

        $deal->labels()->sync($request->labels ?? []);

        if (isset($request->products)) {
            foreach ($request->products as $product) {
                $deal->dealProducts()->create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'product_id' => $product['id'],
                    'price' => $product['price'],
                    'quantity' => $product['quantity'],
                    'amount' => $product['amount'],
                ]);
            }
        }

        return $deal;
    }

    public function update($request, Deal $deal, $person = null, $organization = null, $client = null)
    {
        $deal->update([
            'person_id' => $person->id ?? null,
            'organization_id' => $organization->id ?? null,
            'client_id' => $client->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'expected_close' => $request->expected_close,
            'user_owner_id' => $request->user_owner_id,
            'pipeline_id' => optional(PipelineStage::find($request->pipeline_stage_id))->pipeline?->id,
            'pipeline_stage_id' => $request->pipeline_stage_id ?? null,
        ]);

        $deal->labels()->sync($request->labels ?? []);

        if (isset($request->products)) {
            foreach ($request->products as $product) {
                if (isset($product['deal_product_id']) && $dealProduct = $deal->dealProducts()->where('id', $product['deal_product_id'])->first()) {
                    $dealProduct->update([
                        'product_id' => $product['id'],
                        'price' => $product['price'],
                        'quantity' => $product['quantity'],
                        'amount' => $product['amount'],
                    ]);
                } else {
                    $deal->dealProducts()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'product_id' => $product['id'],
                        'price' => $product['price'],
                        'quantity' => $product['quantity'],
                        'amount' => $product['amount'],
                    ]);
                }
            }
        }

        return $deal;
    }
}
