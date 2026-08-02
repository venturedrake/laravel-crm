<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\QuoteProduct;

class QuoteProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Quote $quote)
    {
        abort(404);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(Quote $quote)
    {
        $quoteProduct = $quote->quoteProducts()->create([
            'external_id' => Uuid::uuid4()->toString(),
            'currency' => $quote->currency,
        ]);

        return view('laravel-crm::quote-products.create', [
            'quoteProduct' => $quoteProduct,
            'index' => $quote->quoteProducts->count() - 1,
        ]);
    }

    public function createProduct()
    {
        return view('laravel-crm::quote-products.create-product', [
            'index' => rand(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request, Quote $quote)
    {
        abort(404);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Quote $quote, QuoteProduct $quoteProduct)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Quote $quote, QuoteProduct $quoteProduct)
    {
        return view('laravel-crm::quote-products.edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Quote $quote, QuoteProduct $quoteProduct)
    {
        abort(404);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Quote $quote, QuoteProduct $quoteProduct)
    {
        abort(404);
    }
}
