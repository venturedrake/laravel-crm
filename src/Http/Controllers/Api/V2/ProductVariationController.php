<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\StoreProductVariationRequest;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\UpdateProductVariationRequest;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\ProductVariationResource;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductVariation;
use VentureDrake\LaravelCrm\Services\ProductService;

/**
 * Variations are part of their product, so they carry no permissions of
 * their own: reading one needs `view` on the product, writing one needs
 * `update` on it. The variation is resolved through the product
 * (scoped binding), so a variation of another product is a 404.
 */
class ProductVariationController extends ApiController
{
    public function __construct(private ProductService $productService) {}

    public function index(Product $product)
    {
        $this->authorize('view', $product);

        return ProductVariationResource::collection(
            $product->productVariations()->with('productPrices')->get()
        );
    }

    public function show(Product $product, ProductVariation $productVariation)
    {
        $this->authorize('view', $product);

        return new ProductVariationResource($productVariation->load('productPrices'));
    }

    public function store(StoreProductVariationRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $variation = $this->productService->createVariation(
            $product,
            $this->variationData($request),
            $request->input('currency')
        );

        return (new ProductVariationResource($variation->refresh()->load('productPrices')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProductVariationRequest $request, Product $product, ProductVariation $productVariation)
    {
        $this->authorize('update', $product);

        $variation = $this->productService->updateVariation(
            $productVariation,
            $this->variationData($request),
            $request->input('currency')
        );

        return new ProductVariationResource($variation->refresh()->load('productPrices'));
    }

    public function destroy(Product $product, ProductVariation $productVariation)
    {
        $this->authorize('update', $product);

        $this->productService->deleteVariation($productVariation);

        return response()->noContent();
    }

    /**
     * Only the keys the request actually sent, so an update leaves the rest
     * alone. `unit_price` follows the same rule: absent leaves the price as
     * it is, an explicit null removes it (the product price then applies).
     * A null `order` is treated as absent, so a new variation is appended.
     */
    private function variationData(FormRequest $request): array
    {
        $data = [];

        foreach (['name', 'code', 'barcode', 'unit_price'] as $key) {
            if ($request->exists($key)) {
                $data[$key] = $request->input($key);
            }
        }

        if ($request->filled('order')) {
            $data['order'] = (int) $request->input('order');
        }

        if ($request->exists('active')) {
            $data['active'] = $request->boolean('active');
        }

        return $data;
    }
}
