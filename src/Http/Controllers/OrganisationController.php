<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Http\Requests\StoreOrganisationRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateOrganisationRequest;
use VentureDrake\LaravelCrm\Models\Organisation;

class OrganisationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Organisation::all()->count() < 30) {
            $organisations = Organisation::latest()->get();
        } else {
            $organisations = Organisation::latest()->paginate(30);
        }
        
        return view('laravel-crm::organisations.index', [
            'organisations' => $organisations,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('laravel-crm::organisations.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOrganisationRequest $request)
    {
        flash('Organisation stored')->success()->important();

        return redirect(route('laravel-crm.organisations.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Organisation $organisation)
    {
        $address = $organisation->getPrimaryAddress();
        
        return view('laravel-crm::organisations.show', [
            'organisation' => $organisation,
            'address' => $address,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Organisation $organisation)
    {
        $address = $organisation->getPrimaryAddress();
        
        return view('laravel-crm::organisations.edit', [
            'organisation' => $organisation,
            'address' => $address
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateOrganisationRequest $request, Organisation $organisation)
    {
        $organisation->update([
            'name' => $request->name,
            'description' => $request->description,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $address = $organisation->getPrimaryAddress();

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
        } else {
            $organisation->addresses()->create([
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
        
        flash('Organisation updated')->success()->important();

        return redirect(route('laravel-crm.organisations.show', $organisation));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Organisation $organisation)
    {
        $organisation->delete();

        flash('Organisation deleted')->success()->important();

        return redirect(route('laravel-crm.organisations.index'));
    }
}
