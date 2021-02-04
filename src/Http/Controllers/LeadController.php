<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Http\Requests\StoreLeadRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateLeadRequest;
use VentureDrake\LaravelCrm\Models\Lead;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (Lead::all()->count() < 30) {
            $leads = Lead::latest()->get();
        } else {
            $leads = Lead::latest()->paginate(30);
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
            'person_name' => $request->person_name,
            'organisation_name' => $request->organisation_name,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'lead_status_id' => 1,
            'user_assigned_id' => $request->user_assigned_id,
        ]);
        
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
        
        return view('laravel-crm::leads.edit', [
            'lead' => $lead,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
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
            'person_name' => $request->person_name,
            'organisation_name' => $request->organisation_name,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'user_assigned_id' => $request->user_assigned_id,
        ]);

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
        } elseif ($phone) {
            $phone->delete();
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
        } elseif ($email) {
            $email->delete();
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
}
