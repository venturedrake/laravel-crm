<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use VentureDrake\LaravelCrm\Models\EmailCampaign;

class EmailCampaignController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', EmailCampaign::class);

        return view('laravel-crm::email-campaigns.index');
    }

    public function create()
    {
        $this->authorize('create', EmailCampaign::class);

        return view('laravel-crm::email-campaigns.create');
    }

    public function show(EmailCampaign $emailCampaign)
    {
        $this->authorize('view', $emailCampaign);

        return view('laravel-crm::email-campaigns.show', ['campaign' => $emailCampaign]);
    }

    public function edit(EmailCampaign $emailCampaign)
    {
        $this->authorize('update', $emailCampaign);

        return view('laravel-crm::email-campaigns.edit', ['campaign' => $emailCampaign]);
    }
}
