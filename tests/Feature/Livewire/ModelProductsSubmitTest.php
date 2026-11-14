<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\ModelProducts;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductPrice;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\TaxRate;

beforeEach(function () {
    $this->actingAsUser();
});

function submitQuoteEditor(): array
{
    $rate = TaxRate::create(['name' => 'GST', 'rate' => 10]);
    $product = Product::create(['name' => 'Measured product', 'tax_rate_id' => $rate->id]);
    $quote = Quote::create(['title' => 'Captured quote', 'currency' => 'USD']);
    $quote->quoteProducts()->create([
        'product_id' => $product->id, 'quantity' => 2, 'price' => 10,
        'amount' => 15, 'tax_amount' => 150, 'tax_rate' => 10,
    ]);
    $editor = Livewire::test(ModelProducts::class, ['model' => $quote, 'currency' => 'USD']);

    return [$editor, $editor->get('products'), $product];
}

test('preparation reprices captured quantity and price and carries the latest comment', function () {
    [$editor, $rows] = submitQuoteEditor();
    $rows[0]['quantity'] = '0.5';
    $rows[0]['unit_price'] = '12.00';
    $rows[0]['comments'] = 'Latest comment';

    $editor->call('prepareForSave', $rows)
        ->assertSet('products.0.quantity', '0.5')
        ->assertSet('products.0.comments', 'Latest comment')
        ->assertSet('products.0.row_key', $rows[0]['row_key'])
        ->assertSet('products.0.quote_product_id', $rows[0]['quote_product_id'])
        ->assertSet('sub_total', 6.0)
        ->assertSet('tax', 0.6)
        ->assertSet('total', 6.6)
        ->assertNotDispatched('model-products-updated');
});

test('preparation keeps a discount when only a comment or money formatting changes', function () {
    [$editor, $rows] = submitQuoteEditor();
    $rows[0]['unit_price'] = '10.00';
    $rows[0]['comments'] = 'Keep discount';

    $editor->call('prepareForSave', $rows)
        ->assertSet('sub_total', 15.0)
        ->assertSet('total', 16.5);
});

test('a captured stale amount cannot undo a preview that finished after capture', function () {
    [$editor, $rows] = submitQuoteEditor();
    $rows[0]['quantity'] = '0.5';
    // The browser snapshot still carries the previous discounted amount.
    $editor->set('products.0.quantity', 0.5);
    $editor->call('prepareForSave', $rows)->assertSet('sub_total', 5.0);
});

test('preparation preserves invalid quantities for parent validation', function ($quantity) {
    [$editor, $rows] = submitQuoteEditor();
    $rows[0]['quantity'] = $quantity;
    $rows[0]['quantity_max'] = 1;

    $editor->call('prepareForSave', $rows)->assertSet('products.0.quantity', $quantity);
})->with(['', null, -1, 1.2345, 99, 'invalid']);

test('preparation applies a newly captured variation price', function () {
    [$editor, $rows, $product] = submitQuoteEditor();
    $variation = $product->productVariations()->create(['name' => 'Large']);
    ProductPrice::create(['product_id' => $product->id, 'product_variation_id' => $variation->id, 'currency' => 'USD', 'unit_price' => 30]);
    $rows[0]['product_variation_id'] = $variation->id;

    $editor->call('prepareForSave', $rows)->assertSet('products.0.unit_price', 30)->assertSet('sub_total', 60.0);
});

test('preparation keeps saved paid invoice pricing', function () {
    $product = Product::create(['name' => 'Paid line', 'tax_rate' => 20]);
    $invoice = Invoice::create(['currency' => 'USD', 'amount_paid' => 1]);
    $invoice->invoiceLines()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 10, 'amount' => 15, 'tax_amount' => 1.5, 'tax_rate' => 10]);
    $editor = Livewire::test(ModelProducts::class, ['model' => $invoice, 'creating' => 'Invoice']);
    $rows = $editor->get('products');
    $rows[0]['comments'] = 'New comment';

    $editor->call('prepareForSave', $rows)->assertSet('sub_total', 15.0)->assertSet('tax', 1.5);
});
