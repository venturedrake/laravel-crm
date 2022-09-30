<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Http\Requests\StoreProductAttributeRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateProductAttributeRequest;
use VentureDrake\LaravelCrm\Models\ProductAttribute;

class ProductAttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (ProductAttribute::all()->count() < 30) {
            $productAttributes = ProductAttribute::latest()->get();
        } else {
            $productAttributes = ProductAttribute::latest()->paginate(30);
        }

        return view('laravel-crm::product-attributes.index', [
            'productAttributes' => $productAttributes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('laravel-crm::product-attributes.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProductAttributeRequest $request)
    {
        ProductAttribute::create([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $request->name,
            'description' => $request->description,
        ]);

        flash(ucfirst(trans('laravel-crm::lang.product_attribute_stored')))->success()->important();

        return redirect(route('laravel-crm.product-attributes.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ProductAttribute $productAttribute)
    {
        return view('laravel-crm::product-attributes.show', [
            'productAttribute' => $productAttribute,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(ProductAttribute $productAttribute)
    {
        return view('laravel-crm::product-attributes.edit', [
            'productAttribute' => $productAttribute,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProductAttributeRequest $request, ProductAttribute $productAttribute)
    {
        $productAttribute->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        flash(ucfirst(trans('laravel-crm::lang.product_attribute_updated')))->success()->important();

        return redirect(route('laravel-crm.product-attributes.show', $productAttribute));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProductAttribute $productAttribute)
    {
        $productAttribute->delete();

        flash(ucfirst(trans('laravel-crm::lang.product_attribute_deleted')))->success()->important();

        return redirect(route('laravel-crm.product-attributes.index'));
    }
}
