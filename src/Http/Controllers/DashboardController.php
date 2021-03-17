<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return redirect(route('laravel-crm.leads.index'));
        
        return view('laravel-crm::index');
    }
}
