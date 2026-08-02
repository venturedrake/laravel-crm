<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\DealProduct;

class DealProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Deal $deal)
    {
        abort(404);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(Deal $deal)
    {
        $dealProduct = $deal->dealProducts()->create([
            'external_id' => Uuid::uuid4()->toString(),
            'currency' => $deal->currency,
        ]);

        return view('laravel-crm::deal-products.create', [
            'dealProduct' => $dealProduct,
            'index' => $deal->dealProducts->count() - 1,
        ]);
    }

    public function createProduct()
    {
        return view('laravel-crm::deal-products.create-product', [
            'index' => rand(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request, Deal $deal)
    {
        abort(404);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Deal $deal, DealProduct $dealProduct)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Deal $deal, DealProduct $dealProduct)
    {
        return view('laravel-crm::deal-products.edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Deal $deal, DealProduct $dealProduct)
    {
        abort(404);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Deal $deal, DealProduct $dealProduct)
    {
        abort(404);
    }
}
