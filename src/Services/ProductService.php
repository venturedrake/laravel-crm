<?php

namespace VentureDrake\LaravelCrm\Services;

use Dcblogdev\Xero\Facades\Xero;
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
            'name' => $request->name,
            'code' => $request->code ?? null,
            'purchase_account' => $request->purchase_account ?? null,
            'sales_account' => $request->sales_account ?? null,
            'product_category_id' => $request->product_category,
            'unit' => $request->unit ?? null,
            'tax_rate_id' => $request->tax_rate_id ?? null,
            'tax_rate' => $request->tax_rate ?? null,
            'description' => $request->description ?? null,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $product->productPrices()->create([
            'unit_price' => $request->unit_price,
            'currency' => $request->currency,
        ]);

        if (Xero::isConnected()) {
            $xeroProduct = Xero::post('Items', [
                'Code' => $product->code,
                'Name' => $product->name,
                'Description' => $product->description,
                'PurchaseDetails' => [
                    'AccountCode' => $product->purchase_account ?? 310,
                ],
                'SalesDetails' => [
                    'UnitPrice' => ($product->getDefaultPrice()->unit_price) ? $product->getDefaultPrice()->unit_price / 100 : null,
                    'AccountCode' => $product->sales_account ?? 200,
                ],
            ]);

            $item = $xeroProduct['body']['Items'][0];

            $product->xeroItem()->updateOrCreate([
                'item_id' => $item['ItemID'],
            ], [
                'code' => $item['Code'],
                'name' => $item['Name'],
                'inventory_tracked' => $item['IsTrackedAsInventory'],
                'is_sold' => $item['IsSold'],
                'is_purchased' => $item['IsPurchased'],
                'purchase_price' => (isset($item['PurchaseDetails']['UnitPrice'])) ? $item['PurchaseDetails']['UnitPrice'] : null,
                'sell_price' => (isset($item['SalesDetails']['UnitPrice'])) ? $item['SalesDetails']['UnitPrice'] : null,
                'purchase_description' => $item['PurchaseDescription'] ?? null,
            ]);
        }

        return $product;
    }

    public function update(Product $product, $request)
    {
        $product->update([
            'name' => $request->name,
            'code' => $request->code ?? null,
            'purchase_account' => $request->purchase_account ?? null,
            'sales_account' => $request->sales_account ?? null,
            'product_category_id' => $request->product_category,
            'unit' => $request->unit ?? null,
            'tax_rate_id' => $request->tax_rate_id ?? null,
            'tax_rate' => $request->tax_rate ?? null,
            'description' => $request->description ?? null,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $productPrice = $product->getDefaultPrice();

        if ($productPrice) {
            $productPrice->update([
                'unit_price' => $request->unit_price,
            ]);
        } else {
            $product->productPrices()->create([
                'unit_price' => $request->unit_price,
                'currency' => $request->currency,
            ]);
        }

        if (Xero::isConnected()) {
            $xeroProduct = Xero::post('Items', [
                'ItemID' => $product->xeroItem->item_id ?? null,
                'Code' => $product->code,
                'Name' => $product->name,
                'Description' => $product->description,
                'PurchaseDetails' => [
                    'AccountCode' => $product->purchase_account ?? 310,
                ],
                'SalesDetails' => [
                    'UnitPrice' => ($product->getDefaultPrice()->unit_price) ? $product->getDefaultPrice()->unit_price / 100 : null,
                    'AccountCode' => $product->sales_account ?? 200,
                ],
            ]);

            $item = $xeroProduct['body']['Items'][0];

            $product->xeroItem()->updateOrCreate([
                'item_id' => $item['ItemID'],
            ], [
                'code' => $item['Code'],
                'name' => $item['Name'],
                'inventory_tracked' => $item['IsTrackedAsInventory'],
                'is_sold' => $item['IsSold'],
                'is_purchased' => $item['IsPurchased'],
                'purchase_price' => (isset($item['PurchaseDetails']['UnitPrice'])) ? $item['PurchaseDetails']['UnitPrice'] : null,
                'sell_price' => (isset($item['SalesDetails']['UnitPrice'])) ? $item['SalesDetails']['UnitPrice'] : null,
                'purchase_description' => $item['PurchaseDescription'] ?? null,
            ]);
        }

        return $product;
    }
}
