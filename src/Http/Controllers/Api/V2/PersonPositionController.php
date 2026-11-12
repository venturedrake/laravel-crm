<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\StorePositionRequest;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\UpdatePositionRequest;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Position;

class PersonPositionController extends PositionController
{
    public function index(Request $request, Person $person)
    {
        return $this->listPositions($request, $person);
    }

    public function show(Person $person, Position $position)
    {
        return $this->showPosition($person, $position);
    }

    public function store(StorePositionRequest $request, Person $person)
    {
        return $this->storePosition($request, $person);
    }

    public function update(UpdatePositionRequest $request, Person $person, Position $position)
    {
        return $this->updatePosition($request, $person, $position);
    }

    public function destroy(Person $person, Position $position)
    {
        return $this->destroyPosition($person, $position);
    }
}
