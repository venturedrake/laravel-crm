<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Portal;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\SettingService;

class QuoteController extends Controller
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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Quote $quote)
    {
        if ($quote->person) {
            $email = $quote->person->getPrimaryEmail();
            $phone = $quote->person->getPrimaryPhone();
            $address = $quote->person->getPrimaryAddress();
        }

        if ($quote->organisation) {
            $organisation_address = $quote->organisation->getPrimaryAddress();
        }

        return view('laravel-crm::portal.quotes.show', [
            'quote' => $quote,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
            'organisation_address' => $organisation_address ?? null,
            'fromName' => $this->settingService->get('organisation_name')->value ?? null,
            'logo' => $this->settingService->get('logo_file')->value ?? null,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function process(Quote $quote, Request $request)
    {
        switch ($request->action) {
            case "accept":
                $quote->update([
                    'accepted_at' => Carbon::now(),
                ]);

                flash(ucfirst(trans('laravel-crm::lang.quote_accepted')))->success()->important();

                break;

            case "reject":
                $quote->update([
                    'rejected_at' => Carbon::now(),
                ]);

                flash(ucfirst(trans('laravel-crm::lang.quote_rejected')))->success()->important();

                break;
        }


        return back();
    }
}
