<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Http\Requests\StoreDealRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateDealRequest;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Services\OrganisationService;
use VentureDrake\LaravelCrm\Services\PersonService;

class DealController extends Controller
{
    /**
     * @var PersonService
     */
    private $personService;

    /**
     * @var OrganisationService
     */
    private $organisationService;

    public function __construct(PersonService $personService, OrganisationService $organisationService)
    {
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
        
        $deal = Deal::create([
            'external_id' => Uuid::uuid4()->toString(),
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'user_owner_id' => $request->user_assigned_id,
            'user_assigned_id' => $request->user_assigned_id,
        ]);
        
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
        if($deal->person){
            $email = $deal->person->getPrimaryEmail();
            $phone = $deal->person->getPrimaryPhone();
            $address = $deal->person->getPrimaryAddress();
        }
        
        if($deal->organisation){
            $organisation_address = $deal->organisation->getPrimaryAddress();
        }
        
        return view('laravel-crm::deals.show', [
            'deal' => $deal,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
            'organisation_address' => $organisation_address ?? null
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
        return view('laravel-crm::deals.edit', [
            'deal' => $deal,
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
}
