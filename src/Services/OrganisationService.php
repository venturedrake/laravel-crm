<?php

namespace VentureDrake\LaravelCrm\Services;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Repositories\OrganisationRepository;

class OrganisationService
{
    /**
     * @var OrganisationRepository
     */
    private $organisationRepository;

    /**
     * LeadService constructor.
     * @param OrganisationRepository $organisationRepository
     */
    public function __construct(OrganisationRepository $organisationRepository)
    {
        $this->organisationRepository = $organisationRepository;
    }

    public function create($request)
    {
        $organisation = Organisation::create([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $request->name,
            'description' => $request->description,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $organisation->addresses()->create([
            'external_id' => Uuid::uuid4()->toString(),
            'line1' => $request->line1,
            'line2' => $request->line2,
            'line3' => $request->line3,
            'suburb' => $request->suburb,
            'state' => $request->state,
            'code' => $request->code,
            'country' => $request->country,
            'primary' => 1,
        ]);
        
        return $organisation;
    }

    public function createFromRelated($request)
    {
        $organisation = Organisation::create([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $request->organisation_name,
            'user_owner_id' => $request->user_owner_id ?? $request->user_assigned_id,
        ]);

        $organisation->addresses()->create([
            'external_id' => Uuid::uuid4()->toString(),
            'line1' => $request->line1,
            'line2' => $request->line2,
            'line3' => $request->line3,
            'suburb' => $request->suburb,
            'state' => $request->state,
            'code' => $request->code,
            'country' => $request->country,
            'primary' => 1,
        ]);

        return $organisation;
    }

    public function update(Organisation $organisation, $request)
    {
        $organisation->update([
            'name' => $request->name,
            'description' => $request->description,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $address = $organisation->getPrimaryAddress();

        if ($address) {
            $address->update([
                'line1' => $request->line1,
                'line2' => $request->line2,
                'line3' => $request->line3,
                'city' => $request->city,
                'state' => $request->state,
                'code' => $request->code,
                'country' => $request->country,
            ]);
        } else {
            $organisation->addresses()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'line1' => $request->line1,
                'line2' => $request->line2,
                'line3' => $request->line3,
                'city' => $request->city,
                'state' => $request->state,
                'code' => $request->code,
                'country' => $request->country,
                'primary' => 1,
            ]);
        }
        
        return $organisation;
    }
}
