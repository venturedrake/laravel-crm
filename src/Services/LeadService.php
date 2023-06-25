<?php

namespace VentureDrake\LaravelCrm\Services;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Repositories\LeadRepository;

class LeadService
{
    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * LeadService constructor.
     * @param LeadRepository $leadRepository
     */
    public function __construct(LeadRepository $leadRepository)
    {
        $this->leadRepository = $leadRepository;
    }

    public function create($request, $person = null, $organisation = null, $client = null)
    {
        $lead = Lead::create([
            'external_id' => Uuid::uuid4()->toString(),
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'client_id' => $client->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'lead_status_id' => 1,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $lead->labels()->sync($request->labels ?? []);

        return $lead;
    }

    public function update($request, Lead $lead, $person = null, $organisation = null, $client = null)
    {
        $lead->update([
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'client_id' => $client->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $lead->labels()->sync($request->labels ?? []);

        return $lead;
    }
}
