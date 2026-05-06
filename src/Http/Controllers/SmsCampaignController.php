<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use VentureDrake\LaravelCrm\Models\SmsCampaign;

class SmsCampaignController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', SmsCampaign::class);

        return view('laravel-crm::sms-campaigns.index');
    }

    public function create()
    {
        $this->authorize('create', SmsCampaign::class);

        return view('laravel-crm::sms-campaigns.create');
    }

    public function show(SmsCampaign $smsCampaign)
    {
        $this->authorize('view', $smsCampaign);

        return view('laravel-crm::sms-campaigns.show', ['campaign' => $smsCampaign]);
    }

    public function edit(SmsCampaign $smsCampaign)
    {
        $this->authorize('update', $smsCampaign);

        return view('laravel-crm::sms-campaigns.edit', ['campaign' => $smsCampaign]);
    }
}
