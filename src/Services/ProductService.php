<?php

namespace VentureDrake\LaravelCrm\Services;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Repositories\ProductRepository;

class ProductService
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * LeadService constructor.
     * @param ProductRepository $productRepository
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function create($request)
    {
        $product = Product::create([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $request->name,
            'code' => $request->code ?? null,
            'unit' => $request->unit ?? null,
            'tax_rate' => $request->tax_rate ?? null,
            'description' => $request->description ?? null,
            'user_owner_id' => $request->user_owner_id,
        ]);
        
        return $product;
    }

    public function update(Product $product, $request)
    {
        $product->update([
            'name' => $request->name,
            'code' => $request->code ?? null,
            'unit' => $request->unit ?? null,
            'tax_rate' => $request->tax_rate ?? null,
            'description' => $request->description ?? null,
            'user_owner_id' => $request->user_owner_id,
        ]);
        
        return $product;
    }
}
