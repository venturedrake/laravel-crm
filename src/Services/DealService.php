<?php

namespace VentureDrake\LaravelCrm\Services;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Repositories\DealRepository;

class DealService
{
    /**
     * @var DealRepository
     */
    private $dealRepository;

    /**
     * LeadService constructor.
     * @param DealRepository $DealRepository
     */
    public function __construct(DealRepository $dealRepository)
    {
        $this->dealRepository = $dealRepository;
    }

    public function create($request, $person = null, $organisation = null)
    {
        $deal = Deal::create([
            'external_id' => Uuid::uuid4()->toString(),
            'lead_id' => $request->lead_id ?? null,
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'expected_close' => $request->expected_close,
            'user_owner_id' => $request->user_assigned_id,
            'user_assigned_id' => $request->user_assigned_id,
        ]);

        $deal->labels()->sync($request->labels ?? []);
        
        return $deal;
    }

    public function update($request, Deal $deal, $person = null, $organisation = null)
    {
        $deal->update([
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'expected_close' => $request->expected_close,
            'user_owner_id' => $request->user_assigned_id,
            'user_assigned_id' => $request->user_assigned_id,
        ]);

        $deal->labels()->sync($request->labels ?? []);
        
        return $deal;
    }
}
