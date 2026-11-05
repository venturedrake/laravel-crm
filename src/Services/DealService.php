<?php

namespace VentureDrake\LaravelCrm\Services;

use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Repositories\DealRepository;
use VentureDrake\LaravelCrm\Traits\ResolvesProductVariation;

class DealService
{
    use ResolvesProductVariation;

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
        // A converted lead already has its deal. Checked here so the form, the
        // API and any other caller are all held to it.
        if (Lead::whereKey($request->lead_id ?? null)->whereNotNull('converted_at')->exists()) {
            throw ValidationException::withMessages([
                'lead_id' => ucfirst(__('laravel-crm::lang.lead_already_converted')),
            ]);
        }

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
                    'product_variation_id' => $this->variationIdFor($product['id'], $product['product_variation_id'] ?? null),
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
                        'product_variation_id' => $this->variationIdFor($product['id'], $product['product_variation_id'] ?? null, $dealProduct->product_variation_id),
                        'price' => $product['price'],
                        'quantity' => $product['quantity'],
                        'amount' => $product['amount'],
                    ]);
                } else {
                    $deal->dealProducts()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'product_id' => $product['id'],
                        'product_variation_id' => $this->variationIdFor($product['id'], $product['product_variation_id'] ?? null),
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
