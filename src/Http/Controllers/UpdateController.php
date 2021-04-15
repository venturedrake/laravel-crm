<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Request;

class UpdateController extends Controller
{
    /**
     * Display update information
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('laravel-crm::updates.index');
    }

    /**
     * Update
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //
    }
}
