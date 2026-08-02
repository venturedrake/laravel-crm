<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\OrderProduct;

class OrderProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Order $order)
    {
        abort(404);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(Order $order)
    {
        $orderProduct = $order->orderProducts()->create([
            'external_id' => Uuid::uuid4()->toString(),
            'currency' => $order->currency,
        ]);

        return view('laravel-crm::order-products.create', [
            'orderProduct' => $orderProduct,
            'index' => $order->orderProducts->count() - 1,
        ]);
    }

    public function createProduct()
    {
        return view('laravel-crm::order-products.create-product', [
            'index' => rand(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request, Order $order)
    {
        abort(404);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Order $order, OrderProduct $orderProduct)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Order $order, OrderProduct $orderProduct)
    {
        return view('laravel-crm::order-products.edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Order $order, OrderProduct $orderProduct)
    {
        abort(404);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Order $order, OrderProduct $orderProduct)
    {
        abort(404);
    }
}
