<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use VentureDrake\LaravelCrm\Models\FeatureStatus;

class FeatureStatusController extends Controller
{
    public function index()
    {
        return view('laravel-crm::settings.feature-statuses.index');
    }

    public function create()
    {
        return view('laravel-crm::settings.feature-statuses.create');
    }

    public function edit(FeatureStatus $featureStatus)
    {
        return view('laravel-crm::settings.feature-statuses.edit', [
            'featureStatus' => $featureStatus,
        ]);
    }
}
