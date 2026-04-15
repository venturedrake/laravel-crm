<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Response;

class DashboardController extends Controller
{
    /**
     * Display the CRM dashboard.
     *
     * @return Response
     */
    public function index()
    {
        return view('laravel-crm::index');
    }
}
