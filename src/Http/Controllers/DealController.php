<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use VentureDrake\LaravelCrm\Http\Requests\StoreDealRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateDealRequest;
use VentureDrake\LaravelCrm\Models\Deal;

class DealController extends Controller
{
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
        return view('laravel-crm::deals.show', [
            'deal' => $deal,
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
