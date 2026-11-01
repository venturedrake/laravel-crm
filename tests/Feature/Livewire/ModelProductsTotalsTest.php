<?php

/*
 * The line-item totals on the quote/order/invoice forms.
 *
 * The money inputs are masked in the browser ($money), so the line values come
 * back as formatted strings — and Livewire can send the products array wholesale
 * rather than one "{index}.{attribute}" at a time. Both used to leave the footer
 * showing 0: the first because "14,000" casts to 14.0, the second because the
 * hook zeroed the totals and then returned early without summing anything.
 */

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\ModelProducts;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\TaxRate;

function quoteWithLines(): Quote
{
    $productA = Product::create(['name' => 'A']);
    $productB = Product::create(['name' => 'B']);

    // Stored totals are deliberately stale — the lines are the source of truth.
    $quote = Quote::create(['title' => 'Q', 'currency' => 'USD', 'subtotal' => 180.13, 'tax' => 18.01, 'total' => 198.14]);

    $quote->quoteProducts()->create([
        'product_id' => $productA->id, 'quantity' => 3, 'price' => 4500,
        // tax_amount has no mutator on the line models, so it is stored in cents.
        'tax_rate' => 10, 'tax_amount' => 135000, 'amount' => 13500,
    ]);

    $quote->quoteProducts()->create([
        'product_id' => $productB->id, 'quantity' => 4, 'price' => 3500,
        'tax_rate' => 10, 'tax_amount' => 140000, 'amount' => 14000,
    ]);

    return $quote;
}

test('it totals the lines it loaded', function () {
    $this->actingAsUser();

    Livewire::test(ModelProducts::class, ['model' => quoteWithLines()])
        ->assertSet('sub_total', 27500.0)
        ->assertSet('tax', 2750.0)
        ->assertSet('total', 30250.0);
});

test('it keeps the totals when the products array is replaced wholesale', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => quoteWithLines()]);

    $component->set('products', $component->get('products'));

    $component->assertSet('sub_total', 27500.0)
        ->assertSet('tax', 2750.0)
        ->assertSet('total', 30250.0);
});

test('it totals masked money values', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => quoteWithLines()]);

    $products = $component->get('products');
    $products[0]['amount'] = '13,500.00';
    $products[0]['tax_amount'] = '1,350.00';
    $products[1]['amount'] = '$14,000.00';
    $products[1]['tax_amount'] = '$1,400.00';

    $component->set('products', $products);

    $component->assertSet('sub_total', 27500.0)
        ->assertSet('tax', 2750.0)
        ->assertSet('total', 30250.0);
});

test('it recalculates a line from a masked unit price', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => quoteWithLines()]);

    $component->set('products.1.unit_price', '1,200.00');

    expect($component->get('products.1.amount'))->toBe(4800.0);
    $component->assertSet('sub_total', 18300.0);
});

test('it retotals when a line is removed', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => quoteWithLines()]);

    $component->call('remove', 0);

    $component->assertSet('sub_total', 14000.0)
        ->assertSet('tax', 1400.0)
        ->assertSet('total', 15400.0);
});

/*
 * Decimal quantities. Quantity is decimal(15,3) so a product can be sold by
 * weight, and the Order-to-Invoice / Order-to-Delivery forms have swapped
 * their bounded `<select>` for a free number input capped server-side.
 */

test('it prices a fractional quantity in full', function () {
    $this->actingAsUser();

    // The old (int) cast truncated 3.5 to 3, silently under-charging by a
    // whole unit while the header still agreed with the lines.
    $component = Livewire::test(ModelProducts::class, ['model' => quoteWithLines()]);

    $component->set('products.1.quantity', 3.5);

    // Line 1 is 3500.00 a unit, so 3.5 of it is 12250.00 - the (int) cast
    // priced it at 10500.00 and lost a whole unit.
    expect($component->get('products.1.amount'))->toBe(12250.0);
    $component->assertSet('sub_total', 25750.0);
});

test('it rounds a typed quantity to 3 decimal places', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => quoteWithLines()]);

    $component->set('products.1.quantity', 3.5555);

    expect($component->get('products.1.quantity'))->toBe(3.556);
});

test('it no longer builds a quantity dropdown', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => quoteWithLines()]);

    expect($component->get('products.0'))->not->toHaveKey('quantities');
});

function orderPartlyInvoiced(): Order
{
    $product = Product::create(['name' => 'By weight']);

    $order = Order::create(['currency' => 'USD']);
    $orderProduct = $order->orderProducts()->create([
        'product_id' => $product->id, 'quantity' => 3.5, 'price' => 1000,
        'tax_rate' => 10, 'tax_amount' => 350000, 'amount' => 35000,
    ]);

    $invoice = Invoice::create(['order_id' => $order->id, 'currency' => 'USD']);
    $invoice->invoiceLines()->create([
        'product_id' => $product->id, 'order_product_id' => $orderProduct->id,
        'quantity' => 1, 'price' => 1000, 'amount' => 10000,
    ]);

    return $order->fresh();
}

test('invoicing from an order seeds the quantity with what is left outstanding', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, [
        'model' => orderPartlyInvoiced(), 'creating' => 'Invoice', 'from' => 'Order',
    ]);

    expect($component->get('products.0.quantity'))->toBe(2.5)
        ->and($component->get('products.0.quantity_max'))->toBe(2.5);
});

test('it clamps a quantity typed above what is outstanding', function () {
    $this->actingAsUser();

    // The `<select>` this replaced was the only guard against over-invoicing,
    // and it only ever existed in the browser.
    $component = Livewire::test(ModelProducts::class, [
        'model' => orderPartlyInvoiced(), 'creating' => 'Invoice', 'from' => 'Order',
    ]);

    $component->set('products.0.quantity', 4);

    expect($component->get('products.0.quantity'))->toBe(2.5);
    $component->assertHasErrors('products.0.quantity');
});

test('it leaves a quantity within what is outstanding alone', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, [
        'model' => orderPartlyInvoiced(), 'creating' => 'Invoice', 'from' => 'Order',
    ]);

    $component->set('products.0.quantity', 1.25);

    expect($component->get('products.0.quantity'))->toBe(1.25);
    $component->assertHasNoErrors('products.0.quantity');
});

test('a plain quote line has no cap and is not clamped', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => quoteWithLines()]);

    $component->set('products.0.quantity', 99.5);

    expect($component->get('products.0.quantity'))->toBe(99.5);
    $component->assertHasNoErrors('products.0.quantity');
});

/*
 * Lines on a half-cent boundary. The form multiplied float dollars and
 * rounded to 2dp, which landed a cent below the half-up rounding the
 * CheckAmount helpers apply to the saved line - so the quote's show page
 * flagged its own lines and hid Send/Accept.
 */

test('a line exactly on the half cent rounds up', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => quoteWithLines()]);

    $component->set('products.0.unit_price', '333.33');
    $component->set('products.0.quantity', 2.5);

    expect($component->get('products.0.amount'))->toBe(833.33);
});

test('a fractional quantity is priced to the nearest cent', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => quoteWithLines()]);

    $component->set('products.0.unit_price', '9.99');
    $component->set('products.0.quantity', 3.5);

    expect($component->get('products.0.amount'))->toBe(34.97);
});

test('two half-cent lines total the cents each line shows', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => quoteWithLines()]);

    foreach ([0, 1] as $index) {
        $component->set("products.$index.unit_price", '9.99');
        $component->set("products.$index.quantity", 0.5);
    }

    // toEqual: a whole 5.0 comes back through the JSON round trip as 5.
    expect($component->get('products.0.amount'))->toEqual(5.0)
        ->and($component->get('products.1.amount'))->toEqual(5.0);
    $component->assertSet('sub_total', 10.0);
});

/*
 * A follow-up invoice from a part-invoiced order. The rows were seeded with
 * the outstanding quantity but kept the order line's full amount and tax, so
 * the second invoice billed the whole order again.
 */

function orderWithTwoPartInvoicedLines(): Order
{
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $whole = Product::create(['name' => 'Whole', 'tax_rate_id' => $gst->id]);
    $weighed = Product::create(['name' => 'Weighed', 'tax_rate_id' => $gst->id]);

    $order = Order::create(['currency' => 'USD']);

    // Line amounts take dollars; tax_amount has no mutator, so it is in cents.
    $wholeLine = $order->orderProducts()->create([
        'product_id' => $whole->id, 'quantity' => 5, 'price' => 2000,
        'tax_rate' => 10, 'tax_amount' => 100000, 'amount' => 10000,
    ]);
    $weighedLine = $order->orderProducts()->create([
        'product_id' => $weighed->id, 'quantity' => 2.5, 'price' => 333.20,
        'tax_rate' => 10, 'tax_amount' => 8330, 'amount' => 833.00,
    ]);

    $invoice = Invoice::create(['order_id' => $order->id, 'currency' => 'USD']);
    $invoice->invoiceLines()->create([
        'product_id' => $whole->id, 'order_product_id' => $wholeLine->id,
        'quantity' => 2, 'price' => 2000, 'amount' => 4000,
    ]);
    $invoice->invoiceLines()->create([
        'product_id' => $weighed->id, 'order_product_id' => $weighedLine->id,
        'quantity' => 1.25, 'price' => 333.20, 'amount' => 416.50,
    ]);

    return $order->fresh();
}

test('a follow-up invoice is priced from the quantity still outstanding', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, [
        'model' => orderWithTwoPartInvoicedLines(), 'creating' => 'Invoice', 'from' => 'Order',
    ]);

    expect($component->get('products.0.quantity'))->toEqual(3)
        ->and($component->get('products.0.amount'))->toEqual(6000)
        ->and($component->get('products.0.tax_amount'))->toEqual(600)
        ->and($component->get('products.1.quantity'))->toEqual(1.25)
        ->and($component->get('products.1.amount'))->toEqual(416.5)
        ->and($component->get('products.1.tax_amount'))->toEqual(41.65);

    $component->assertSet('sub_total', 6416.5)
        ->assertSet('tax', 641.65)
        ->assertSet('total', 7058.15);
});

test('a line already invoiced in full is left off the follow-up invoice', function () {
    $this->actingAsUser();

    $order = orderWithTwoPartInvoicedLines();
    $wholeLine = $order->orderProducts()->orderBy('id')->first();

    // Invoice the remaining 3 of the first line.
    Invoice::create(['order_id' => $order->id, 'currency' => 'USD'])->invoiceLines()->create([
        'product_id' => $wholeLine->product_id, 'order_product_id' => $wholeLine->id,
        'quantity' => 3, 'price' => 2000, 'amount' => 6000,
    ]);

    $component = Livewire::test(ModelProducts::class, [
        'model' => $order->fresh(), 'creating' => 'Invoice', 'from' => 'Order',
    ]);

    expect($component->get('products'))->toHaveCount(1)
        ->and($component->get('products.0.order_product_id'))->not->toBe($wholeLine->id);
    $component->assertSet('sub_total', 416.5);
});
