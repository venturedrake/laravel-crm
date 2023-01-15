<?php

namespace VentureDrake\LaravelCrm\Repositories;

use VentureDrake\LaravelCrm\Models\Invoice;

class InvoiceRepository
{
    public function all()
    {
        return Invoice::all();
    }

    public function find($id)
    {
        return Invoice::find($id);
    }
}
