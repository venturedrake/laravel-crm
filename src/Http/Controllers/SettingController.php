<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use VentureDrake\LaravelCrm\Http\Requests\UpdateSettingRequest;
use VentureDrake\LaravelCrm\Services\SettingService;

class SettingController extends Controller
{
    /**
     * @var SettingService
     */
    private $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        $organisationName = $this->settingService->get('organisation_name');
        $language = $this->settingService->get('language');
        $country = $this->settingService->get('country');
        $currency = $this->settingService->get('currency');
        
        return view('laravel-crm::settings.edit', [
            'organisationName' => $organisationName,
            'language' => $language,
            'country' => $country,
            'currency' => $currency,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSettingRequest $request)
    {
        $this->settingService->set('organisation_name', $request->organisation_name);
        $this->settingService->set('language', $request->language);
        $this->settingService->set('country', $request->country);
        $this->settingService->set('currency', $request->currency);

        flash('Settings updated')->success()->important();

        return back();
    }
}
