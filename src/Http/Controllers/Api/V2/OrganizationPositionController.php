<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\StorePositionRequest;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\UpdatePositionRequest;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Position;

class OrganizationPositionController extends PositionController
{
    public function index(Request $request, Organization $organization)
    {
        return $this->listPositions($request, $organization);
    }

    public function show(Organization $organization, Position $position)
    {
        return $this->showPosition($organization, $position);
    }

    public function store(StorePositionRequest $request, Organization $organization)
    {
        return $this->storePosition($request, $organization);
    }

    public function update(UpdatePositionRequest $request, Organization $organization, Position $position)
    {
        return $this->updatePosition($request, $organization, $position);
    }

    public function destroy(Organization $organization, Position $position)
    {
        return $this->destroyPosition($organization, $position);
    }
}
