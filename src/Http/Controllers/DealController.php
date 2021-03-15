<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Carbon\Carbon;
use VentureDrake\LaravelCrm\Http\Requests\StoreDealRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateDealRequest;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\DealService;
use VentureDrake\LaravelCrm\Services\OrganisationService;
use VentureDrake\LaravelCrm\Services\PersonService;

class DealController extends Controller
{
    /**
     * @var DealService
     */
    private $dealService;
    
    /**
     * @var PersonService
     */
    private $personService;

    /**
     * @var OrganisationService
     */
    private $organisationService;

    public function __construct(DealService $dealService, PersonService $personService, OrganisationService $organisationService)
    {
        $this->dealService = $dealService;
        $this->personService = $personService;
        $this->organisationService = $organisationService;
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Deal::all()->count() < 30) {
            $deals = Deal::latest()->get();
        } else {
            $deals = Deal::latest()->paginate(30);
        }

        return view('laravel-crm::deals.index', [
            'deals' => $deals,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('laravel-crm::deals.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDealRequest $request)
    {
        if ($request->person_name && ! $request->person_id) {
            $person = $this->personService->createFromRelated($request);
        }

        if ($request->organisation_name && ! $request->rganisation_id) {
            $organisation = $this->organisationService->createFromRelated($request);
        }
        
        $this->dealService->create($request, $person, $organisation);
        
        flash('Deal stored')->success()->important();

        return redirect(route('laravel-crm.deals.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Deal $deal)
    {
        if ($deal->person) {
            $email = $deal->person->getPrimaryEmail();
            $phone = $deal->person->getPrimaryPhone();
            $address = $deal->person->getPrimaryAddress();
        }
        
        if ($deal->organisation) {
            $organisation_address = $deal->organisation->getPrimaryAddress();
        }
        
        return view('laravel-crm::deals.show', [
            'deal' => $deal,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
            'organisation_address' => $organisation_address ?? null,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Deal $deal)
    {
        if ($deal->person) {
            $email = $deal->person->getPrimaryEmail();
            $phone = $deal->person->getPrimaryPhone();
        }

        if ($deal->organisation) {
            $address = $deal->organisation->getPrimaryAddress();
        }
        
        return view('laravel-crm::deals.edit', [
            'deal' => $deal,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDealRequest $request, Deal $deal)
    {
        if ($request->person_name && ! $request->person_id) {
            $person = $this->personService->createFromRelated($request);
        } elseif ($request->person_id) {
            $person = Person::find($request->person_id);
        }

        if ($request->organisation_name && ! $request->organisation_id) {
            $organisation = $this->organisationService->createFromRelated($request);
        } elseif ($request->person_id) {
            $organisation = Organisation::find($request->organisation_id);
        }

        $deal = $this->dealService->update($request, $deal, $person, $organisation);
        
        flash('Deal updated')->success()->important();

        return redirect(route('laravel-crm.deals.show', $deal));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Deal $deal)
    {
        $deal->delete();

        flash('Deal deleted')->success()->important();

        return redirect(route('laravel-crm.deals.index'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function won(Deal $deal)
    {
        $deal->update([
            'closed_status' => 'won',
            'closed_at' => Carbon::now(),
        ]);
        
        flash('Deal won')->success()->important();

        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function lost(Deal $deal)
    {
        $deal->update([
            'closed_status' => 'lost',
            'closed_at' => Carbon::now(),
        ]);
        
        flash('Deal lost')->success()->important();

        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reopen(Deal $deal)
    {
        $deal->update([
            'closed_status' => null,
            'closed_at' => null,
        ]);
        
        flash('Deal reopened')->success()->important();

        return back();
    }
}
