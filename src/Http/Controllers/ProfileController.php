<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     *
     * @return Response
     */
    public function show()
    {
        return view('laravel-crm::profile.show', [
            'user' => auth()->user(),
        ]);
    }
}
