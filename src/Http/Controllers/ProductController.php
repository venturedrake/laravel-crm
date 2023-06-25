<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Http\Requests\StoreProductRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateProductRequest;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Services\ProductService;

class ProductController extends Controller
{
    /**
     * @var ProductService
     */
    private $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Product::resetSearchValue($request);
        $params = $request->except('_token');

        if (Product::filter($params)->get()->count() < 30) {
            $products = Product::filter($params)->latest()->get();
        } else {
            $products = Product::filter($params)->latest()->paginate(30);
        }

        return view('laravel-crm::products.index', [
            'products' => $products,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('laravel-crm::products.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProductRequest $request)
    {
        $product = $this->productService->create($request);

        flash(ucfirst(trans('laravel-crm::lang.product_stored')))->success()->important();

        return redirect(route('laravel-crm.products.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        return view('laravel-crm::products.show', [
            'product' => $product,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        return view('laravel-crm::products.edit', [
            'product' => $product,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $product = $this->productService->update($product, $request);

        flash(ucfirst(trans('laravel-crm::lang.product_updated')))->success()->important();

        return redirect(route('laravel-crm.products.show', $product));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $product->delete();

        flash(ucfirst(trans('laravel-crm::lang.product_deleted')))->success()->important();

        return redirect(route('laravel-crm.products.index'));
    }

    public function search(Request $request)
    {
        $searchValue = Product::searchValue($request);

        if (! $searchValue || trim($searchValue) == '') {
            return redirect(route('laravel-crm.products.index'));
        }

        $products = Product::all()->filter(function ($record) use ($searchValue) {
            foreach ($record->getSearchable() as $field) {
                if (Str::contains(strtolower($record->{$field}), strtolower($searchValue))) {
                    return $record;
                }
            }
        });

        return view('laravel-crm::products.index', [
            'products' => $products,
            'searchValue' => $searchValue ?? null,
        ]);
    }

    public function autocomplete(Product $product)
    {
        $productPrice = $product->getDefaultPrice();

        return response()->json([
            'price' => ($productPrice->unit_price) ? $productPrice->unit_price / 100 : null,
        ]);
    }
}
