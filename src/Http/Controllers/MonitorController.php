<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use VentureDrake\LaravelCrm\Models\Monitor;

class MonitorController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Monitor::class);

        return view('laravel-crm::monitors.index');
    }

    public function create()
    {
        $this->authorize('create', Monitor::class);

        return view('laravel-crm::monitors.create');
    }

    public function show(Monitor $monitor)
    {
        $this->authorize('view', $monitor);

        return view('laravel-crm::monitors.show', ['monitor' => $monitor]);
    }

    public function edit(Monitor $monitor)
    {
        $this->authorize('update', $monitor);

        return view('laravel-crm::monitors.edit', ['monitor' => $monitor]);
    }
}
