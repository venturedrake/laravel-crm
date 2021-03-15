<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Http\Requests\StoreLeadRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateLeadRequest;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\DealService;
use VentureDrake\LaravelCrm\Services\OrganisationService;
use VentureDrake\LaravelCrm\Services\PersonService;

class LeadController extends Controller
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
        if (Lead::whereNull('converted_at')->get()->count() < 30) {
            $leads = Lead::latest()->get();
        } else {
            $leads = Lead::whereNull('converted_at')->latest()->paginate(30);
        }
        
        return view('laravel-crm::leads.index', [
            'leads' => $leads,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('laravel-crm::leads.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreLeadRequest $request)
    {
        $lead = Lead::create([
            'external_id' => Uuid::uuid4()->toString(),
            'person_id' => $request->person_id,
            'person_name' => $request->person_name,
            'organisation_id' => $request->organisation_id,
            'organisation_name' => $request->organisation_name,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'lead_status_id' => 1,
            'user_assigned_id' => $request->user_assigned_id,
        ]);

        $lead->labels()->sync($request->labels ?? []);
        
        if ($request->phone) {
            $lead->phones()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'number' => $request->phone,
                'type' => $request->phone_type,
                'primary' => 1,
            ]);
        }

        if ($request->email) {
            $lead->emails()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'address' => $request->email,
                'type' => $request->email_type,
                'primary' => 1,
            ]);
        }

        flash('Lead stored')->success()->important();
        
        return redirect(route('laravel-crm.leads.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Lead  $lead
     * @return \Illuminate\Http\Response
     */
    public function show(Lead $lead)
    {
        $email = $lead->getPrimaryEmail();
        $phone = $lead->getPrimaryPhone();
        $address = $lead->getPrimaryAddress();
        
        return view('laravel-crm::leads.show', [
            'lead' => $lead,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Lead  $lead
     * @return \Illuminate\Http\Response
     */
    public function edit(Lead $lead)
    {
        $email = $lead->getPrimaryEmail();
        $phone = $lead->getPrimaryPhone();
        $address = $lead->getPrimaryAddress();
        
        return view('laravel-crm::leads.edit', [
            'lead' => $lead,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Lead  $lead
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateLeadRequest $request, Lead $lead)
    {
        $lead->update([
            'person_id' => $request->person_id,
            'person_name' => $request->person_name,
            'organisation_id' => $request->organisation_id,
            'organisation_name' => $request->organisation_name,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'user_assigned_id' => $request->user_assigned_id,
        ]);
        
        $lead->labels()->sync($request->labels ?? []);
        
        $email = $lead->getPrimaryEmail();
        $phone = $lead->getPrimaryPhone();
        $address = $lead->getPrimaryAddress();

        if ($request->phone && $phone) {
            $phone->update([
                'number' => $request->phone,
                'type' => $request->phone_type,
            ]);
        } elseif ($request->phone) {
            $lead->phones()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'number' => $request->phone,
                'type' => $request->phone_type,
                'primary' => 1,
            ]);
        }

        if ($request->email && $email) {
            $email->update([
                'address' => $request->email,
                'type' => $request->email_type,
            ]);
        } elseif ($request->email) {
            $lead->emails()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'address' => $request->email,
                'type' => $request->email_type,
                'primary' => 1,
            ]);
        }

        if ($address) {
            $address->update([
                'line1' => $request->line1,
                'line2' => $request->line2,
                'line3' => $request->line3,
                'suburb' => $request->suburb,
                'state' => $request->state,
                'code' => $request->code,
                'country' => $request->country,
            ]);
        } elseif ($request->email) {
            $lead->addresses()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'line1' => $request->line1,
                'line2' => $request->line2,
                'line3' => $request->line3,
                'suburb' => $request->suburb,
                'state' => $request->state,
                'code' => $request->code,
                'country' => $request->country,
                'primary' => 1,
            ]);
        }

        flash('Lead updated')->success()->important();
        
        return redirect(route('laravel-crm.leads.show', $lead));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Lead  $lead
     * @return \Illuminate\Http\Response
     */
    public function destroy(Lead $lead)
    {
        $lead->delete();
        
        flash('Lead deleted')->success()->important();
        
        return redirect(route('laravel-crm.leads.index'));
    }

    /**
     * Show the form for converting the specified resource.
     *
     * @param  \App\Lead  $lead
     * @return \Illuminate\Http\Response
     */
    public function convertToDeal(Lead $lead)
    {
        $email = $lead->getPrimaryEmail();
        $phone = $lead->getPrimaryPhone();
        $address = $lead->getPrimaryAddress();

        return view('laravel-crm::leads.convert', [
            'lead' => $lead,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeAsDeal(StoreLeadRequest $request, Lead $lead)
    {
        if ($request->person_name && ! $request->person_id) {
            $person = $this->personService->createFromRelated($request);
        } elseif ($request->person_id) {
            $person = Person::find($request->person_id);
        }

        if ($request->organisation_name && ! $request->organisation_id) {
            $organisation = $this->organisationService->createFromRelated($request);
        } elseif ($request->organisation_id) {
            $organisation = Organisation::find($request->organisation_id);
        }

        $this->dealService->create($request, $person ?? null, $organisation ?? null);

        $lead->update([
            'converted_at' => Carbon::now(),
        ]);
        
        flash('Lead converted to deal')->success()->important();

        return redirect(route('laravel-crm.leads.index'));
    }
}
