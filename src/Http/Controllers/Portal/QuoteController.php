<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Portal;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VentureDrake\LaravelCrm\Models\Quote;

class QuoteController extends Controller
{
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
     * @param  int  $id
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
        ]);
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function accept(Quote $quote)
    {
        $quote->update([
            'accepted_at' => Carbon::now(),
        ]);
        
        flash(ucfirst(trans('laravel-crm::lang.quote_accepted')))->success()->important();

        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reject(Quote $quote)
    {
        $quote->update([
            'rejected_at' => Carbon::now(),
        ]);
        
        flash(ucfirst(trans('laravel-crm::lang.quote_rejected')))->success()->important();

        return back();
    }
}