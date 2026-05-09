<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use VentureDrake\LaravelCrm\Models\LeadSource;

class LeadSourceController extends Controller
{
    public function index()
    {
        return view('laravel-crm::settings.lead-sources.index');
    }

    public function create()
    {
        return view('laravel-crm::settings.lead-sources.create');
    }

    public function show(LeadSource $leadSource)
    {
        return view('laravel-crm::settings.lead-sources.show', [
            'leadSource' => $leadSource,
        ]);
    }

    public function edit(LeadSource $leadSource)
    {
        return view('laravel-crm::settings.lead-sources.edit', [
            'leadSource' => $leadSource,
        ]);
    }
}
