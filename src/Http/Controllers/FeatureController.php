<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use VentureDrake\LaravelCrm\Models\Feature;

class FeatureController extends Controller
{
    public function index()
    {
        return view('laravel-crm::features.index');
    }

    public function create()
    {
        return view('laravel-crm::features.create');
    }

    public function show(Feature $feature)
    {
        return view('laravel-crm::features.show', compact('feature'));
    }

    public function edit(Feature $feature)
    {
        return view('laravel-crm::features.edit', compact('feature'));
    }

    public function board()
    {
        return view('laravel-crm::features.board');
    }
}
