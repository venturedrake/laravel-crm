<?php

namespace VentureDrake\LaravelCrm\Repositories;

use VentureDrake\LaravelCrm\Models\Quote;

class QuoteRepository
{
    public function all()
    {
        return Quote::all();
    }

    public function find($id)
    {
        return Quote::find($id);
    }
}
