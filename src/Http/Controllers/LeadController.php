<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
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
        if (Lead::all()->count() < 5) {
            $leads = Lead::latest()->get();
        } else {
            $leads = Lead::latest()->paginate(5);
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
    public function store(\VentureDrake\LaravelCrm\Http\Requests\StoreLeadRequest $request)
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

        flash('Lead stored')->success();
        
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
        return view('laravel-crm::leads.show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Lead  $lead
     * @return \Illuminate\Http\Response
     */
    public function edit(Lead $lead)
    {
        return view('laravel-crm::leads.edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Lead  $lead
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Lead $lead)
    {
        return redirect(route('laravel-crm.leads.index'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Lead  $lead
     * @return \Illuminate\Http\Response
     */
    public function destroy(Lead $lead)
    {
        return redirect(route('laravel-crm.leads.index'));
    }
}
