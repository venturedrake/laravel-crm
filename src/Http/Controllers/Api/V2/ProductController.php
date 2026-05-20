<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\StoreProductRequest;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\UpdateProductRequest;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\ProductResource;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrm\Services\ProductService;

class ProductController extends ApiController
{
    public function __construct(private ProductService $productService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::query()->with(['ownerUser', 'productCategory', 'productPrices']);

        if ($request->filled('user_owner_id')) {
            $query->where('user_owner_id', $request->input('user_owner_id'));
        }

        if ($request->filled('active')) {
            $query->where('active', filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN));
        }

        $query = $this->applySort(
            $query,
            $request,
            ['created_at', 'updated_at', 'name', 'code'],
            '-created_at'
        );

        $products = $query->paginate($this->perPage($request))->withQueryString();

        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        $this->authorize('view', $product);

        $product->load(['ownerUser', 'productCategory', 'productPrices']);

        return new ProductResource($product);
    }

    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $payload = $this->buildPayload($request);

        $product = $this->productService->create($payload);

        $product->refresh()->load(['ownerUser', 'productCategory', 'productPrices']);

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $payload = $this->buildPayload($request, $product);

        $this->productService->update($product, $payload);

        $product->refresh()->load(['ownerUser', 'productCategory', 'productPrices']);

        return new ProductResource($product);
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->noContent();
    }

    private function buildPayload(FormRequest $request, ?Product $existing = null): object
    {
        $productCategory = $request->filled('product_category_id')
            ? ProductCategory::where('external_id', $request->input('product_category_id'))->first()
            : null;

        $existingDefaultPrice = $existing?->productPrices()->first();

        return (object) [
            'name' => $request->input('name', $existing?->name),
            'code' => $request->input('code', $existing?->code),
            'barcode' => $request->input('barcode', $existing?->barcode),
            'description' => $request->input('description', $existing?->description),
            'unit' => $request->input('unit', $existing?->unit),
            'unit_price' => $request->input(
                'unit_price',
                $existingDefaultPrice && $existingDefaultPrice->unit_price !== null
                    ? $existingDefaultPrice->unit_price / 100
                    : null
            ),
            'currency' => $request->input('currency', $existingDefaultPrice?->currency) ?: 'USD',
            'tax_rate' => $request->input('tax_rate', $existing?->tax_rate),
            'tax_rate_id' => $request->input('tax_rate_id', $existing?->tax_rate_id),
            'product_category' => $productCategory?->id ?? $existing?->product_category_id,
            'purchase_account' => $request->input('purchase_account', $existing?->purchase_account),
            'sales_account' => $request->input('sales_account', $existing?->sales_account),
            'user_owner_id' => $request->input('user_owner_id', $existing?->user_owner_id),
        ];
    }
}
