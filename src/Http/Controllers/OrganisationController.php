<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use VentureDrake\LaravelCrm\Http\Requests\StoreOrganisationRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateOrganisationRequest;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Services\OrganisationService;

class OrganisationController extends Controller
{
    /**
     * @var OrganisationService
     */
    private $organisationService;

    public function __construct(OrganisationService $organisationService)
    {
        $this->organisationService = $organisationService;
    }

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
        $organisation = $this->organisationService->create($request);

        $organisation->labels()->sync($request->labels ?? []);
        
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
            'address' => $address,
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
        $this->organisationService->update($organisation, $request);

        $organisation->labels()->sync($request->labels ?? []);
        
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
