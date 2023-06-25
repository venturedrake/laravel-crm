<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use DB;
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
        $timezone = $this->settingService->get('timezone');
        $logoFile = $this->settingService->get('logo_file');
        $quotePrefix = $this->settingService->get('quote_prefix');
        $orderPrefix = $this->settingService->get('order_prefix');
        $invoicePrefix = $this->settingService->get('invoice_prefix');
        $quoteTerms = $this->settingService->get('quote_terms');
        $invoiceTerms = $this->settingService->get('invoice_terms');
        $dateFormat = $this->settingService->get('date_format');
        $timeFormat = $this->settingService->get('time_format');
        $showRelatedActivity = $this->settingService->get('show_related_activity');

        return view('laravel-crm::settings.edit', [
            'organisationName' => $organisationName,
            'language' => $language,
            'country' => $country,
            'currency' => $currency,
            'timezone' => $timezone,
            'logoFile' => $logoFile,
            'quotePrefix' => $quotePrefix,
            'orderPrefix' => $orderPrefix,
            'invoicePrefix' => $invoicePrefix,
            'quoteTerms' => $quoteTerms,
            'invoiceTerms' => $invoiceTerms,
            'dateFormat' => $dateFormat,
            'timeFormat' => $timeFormat,
            'showRelatedActivity' => $showRelatedActivity,
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
        $this->settingService->set('timezone', $request->timezone);

        if($request->quote_prefix) {
            $this->settingService->set('quote_prefix', $request->quote_prefix);
        }

        if($request->order_prefix) {
            $this->settingService->set('order_prefix', $request->order_prefix);
        }

        if($request->invoice_prefix) {
            $this->settingService->set('invoice_prefix', $request->invoice_prefix);
        }

        if ($request->quote_terms) {
            $this->settingService->set('quote_terms', $request->quote_terms);
        }

        if ($request->invoice_terms) {
            $this->settingService->set('invoice_terms', $request->invoice_terms);
        }

        $this->settingService->set('date_format', $request->date_format);
        $this->settingService->set('time_format', $request->time_format);

        if ($file = $request->file('logo')) {
            if (config('laravel-crm.teams') && auth()->user()->currentTeam) {
                $filePath = 'laravel-crm/'.auth()->user()->currentTeam->id;
            } else {
                $filePath = 'laravel-crm';
            }

            $file->move(storage_path('app/public/'.$filePath), $file->getClientOriginalName());
            $this->settingService->set('logo_file', $filePath.'/'.$file->getClientOriginalName());
            $this->settingService->set('logo_file_name', $file->getClientOriginalName());
        }

        if ($request->organisation_name && config('laravel-crm.teams') && auth()->user()->currentTeam) {
            DB::table("teams")
                ->where("id", auth()->user()->currentTeam->id)
                ->update(["name" => $request->organisation_name]);
        }

        $this->settingService->set('show_related_activity', (($request->show_related_activity == 'on') ? 1 : 0));

        flash(ucfirst(trans('laravel-crm::lang.settings_updated')))->success()->important();

        return back();
    }
}
