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
        return view('laravel-crm::index');
    }
}
