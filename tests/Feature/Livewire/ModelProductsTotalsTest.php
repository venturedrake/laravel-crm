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
    // The form taxes a repriced row at its product's rate, as the services do.
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $productA = Product::create(['name' => 'A', 'tax_rate_id' => $gst->id]);
    $productB = Product::create(['name' => 'B', 'tax_rate_id' => $gst->id]);

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

test('the over-quantity nudge is worded like the submit-time rule', function () {
    $this->actingAsUser();

    // The same sentence comes from two places: QuantityWithinRemaining wraps it
    // in ucfirst(), the live nudge in the component did not, so the inline
    // message started lower-case while every other validation message did not.
    $component = Livewire::test(ModelProducts::class, [
        'model' => orderPartlyInvoiced(), 'creating' => 'Invoice', 'from' => 'Order',
    ]);

    $component->set('products.0.quantity', 4);

    expect($component->errors()->get('products.0.quantity')[0])
        ->toBe('Quantity cannot be more than the 2.5 remaining');
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

/*
 * A quantity set to 0 in a wholesale update. The money mask reformats every
 * row's read-only amount, so Livewire sends `products` as one update rather
 * than "{index}.quantity" - and that path only re-summed the rows, leaving the
 * zeroed row's amount in the totals.
 */

test('a row zeroed in a wholesale update drops out of the totals', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => quoteWithLines()]);

    $products = $component->get('products');
    $products[0]['quantity'] = 0;
    // The mask's reformatting of the other row's amount.
    $products[1]['amount'] = '14,000.00';

    $component->set('products', $products);

    expect($component->get('products.0.amount'))->toEqual(0)
        ->and($component->get('products.0.tax_amount'))->toEqual(0);

    $component->assertSet('sub_total', 14000.0)
        ->assertSet('tax', 1400.0)
        ->assertSet('total', 15400.0);
});

test('a wholesale update reprices a row whose quantity changed', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => quoteWithLines()]);

    $products = $component->get('products');
    $products[1]['quantity'] = 2;

    $component->set('products', $products);

    // 2 x 3500.00 at the product's 10%.
    expect($component->get('products.1.amount'))->toEqual(7000)
        ->and($component->get('products.1.tax_amount'))->toEqual(700);

    $component->assertSet('sub_total', 20500.0)
        ->assertSet('tax', 2050.0);
});

/*
 * A wholesale update resends every row. Only a row whose quantity or price
 * changed is repriced; the rest keep their amount, so a quote line saved
 * through the API at less than price x quantity is not quietly re-billed.
 */

test('a wholesale update leaves the amount of a row it did not change', function () {
    $this->actingAsUser();

    $quote = quoteWithLines();
    // 3 x 4500.00 discounted to 12000.00.
    $quote->quoteProducts()->orderBy('id')->first()->update(['amount' => 12000, 'tax_amount' => 120000]);

    $component = Livewire::test(ModelProducts::class, ['model' => $quote->fresh()]);

    $products = $component->get('products');
    $products[0]['amount'] = '12,000.00'; // the mask's reformatting
    $products[1]['quantity'] = 2;

    $component->set('products', $products);

    // The unchanged amount stays as the mask sent it, so the browser does
    // not see it rewritten and resend every row on the next update.
    expect($component->get('products.0.amount'))->toBe('12,000.00')
        ->and($component->get('products.0.tax_amount'))->toEqual(1200)
        ->and($component->get('products.1.amount'))->toEqual(7000);

    $component->assertSet('sub_total', 19000.0)
        ->assertSet('tax', 1900.0);
});

/**
 * quoteWithLines() with its first line (3 x 4500.00) discounted to 12000.00,
 * as the API can save it.
 */
function quoteWithDiscountedLine(): Quote
{
    $quote = quoteWithLines();
    $quote->quoteProducts()->orderBy('id')->first()->update(['amount' => 12000, 'tax_amount' => 120000]);

    return $quote->fresh();
}

test('a comment typed on a discounted row leaves its amount', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => quoteWithDiscountedLine()]);

    $component->set('products.0.comments', 'Agreed price');

    expect($component->get('products.0.amount'))->toEqual(12000)
        ->and($component->get('products.0.tax_amount'))->toEqual(1200);
    $component->assertSet('sub_total', 26000.0);
});

test('the mask reformatting a discounted amount does not reprice it', function () {
    $this->actingAsUser();

    // A single-row form never gets a wholesale update, so this arrives as
    // "0.amount" on its own.
    $quote = quoteWithDiscountedLine();
    $quote->quoteProducts()->orderBy('id')->skip(1)->first()->delete();

    $component = Livewire::test(ModelProducts::class, ['model' => $quote->fresh()]);

    $component->set('products.0.amount', '12,000.00');

    expect($component->get('products.0.amount'))->toBe('12,000.00');
    $component->assertSet('sub_total', 12000.0);
});

/*
 * Variation and product picks. The mask keeps Livewire sending the array
 * wholesale, so a pick can arrive there rather than as its own update - and
 * the wholesale path only looked at quantity and price, so the row kept the
 * base product's price.
 */

function quoteWithVariationLines(): array
{
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $product = Product::create(['name' => 'Shirt', 'tax_rate_id' => $gst->id]);
    $product->productPrices()->create(['unit_price' => 10, 'currency' => 'USD']);

    $large = $product->productVariations()->create(['name' => 'Large']);
    $large->productPrices()->create(['product_id' => $product->id, 'unit_price' => 50, 'currency' => 'USD']);

    $quote = Quote::create(['title' => 'Q', 'currency' => 'USD']);

    foreach ([1, 2] as $order) {
        $quote->quoteProducts()->create([
            'product_id' => $product->id, 'quantity' => 1, 'price' => 10,
            'tax_rate' => 10, 'tax_amount' => 100, 'amount' => 10, 'order' => $order,
        ]);
    }

    return [$quote->fresh(), $large];
}

test('a variation picked in a wholesale update takes its price', function () {
    $this->actingAsUser();

    [$quote, $large] = quoteWithVariationLines();

    $component = Livewire::test(ModelProducts::class, ['model' => $quote, 'currency' => 'USD']);

    $products = $component->get('products');
    $products[0]['product_variation_id'] = $large->id;

    $component->set('products', $products);

    expect($component->get('products.0.product_variation_id'))->toBe($large->id)
        ->and($component->get('products.0.unit_price'))->toEqual(50)
        ->and($component->get('products.0.amount'))->toEqual(50)
        ->and($component->get('products.0.tax_amount'))->toEqual(5)
        ->and($component->get('products.1.amount'))->toEqual(10);

    $component->assertSet('sub_total', 60.0);
});

test('a variation that is not the product\'s is dropped in a wholesale update', function () {
    $this->actingAsUser();

    [$quote] = quoteWithVariationLines();
    $other = Product::create(['name' => 'Other'])->productVariations()->create(['name' => 'Foreign']);

    $component = Livewire::test(ModelProducts::class, ['model' => $quote, 'currency' => 'USD']);

    $products = $component->get('products');
    $products[0]['product_variation_id'] = $other->id;

    $component->set('products', $products);

    expect($component->get('products.0.product_variation_id'))->toBeNull()
        ->and($component->get('products.0.unit_price'))->toEqual(10);
});

test('an invoice row is always priced from its unit price and quantity', function () {
    $this->actingAsUser();

    // An amount out of step with price x quantity - saved by the old
    // follow-up-invoice bug. InvoiceService reprices it on save, so the form
    // shows what will be saved rather than keeping it.
    $product = Product::create(['name' => 'Widget']);
    $invoice = Invoice::create(['currency' => 'USD']);
    $invoice->invoiceLines()->create([
        'product_id' => $product->id, 'quantity' => 1, 'price' => 100, 'amount' => 300,
    ]);

    $component = Livewire::test(ModelProducts::class, ['model' => $invoice->fresh(), 'creating' => 'Invoice']);

    $component->set('products.0.comments', 'Note');

    expect($component->get('products.0.amount'))->toEqual(100);
});

test('a quantity typed on a discounted row reprices it', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => quoteWithDiscountedLine()]);

    $component->set('products.0.quantity', 1);

    expect($component->get('products.0.amount'))->toEqual(4500);
});

test('every row changed in one request is repriced', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => quoteWithLines()]);

    $products = $component->get('products');
    $products[0]['quantity'] = 1;

    // Two whole-row updates in one request. Livewire applies both before
    // either "updated" hook runs, so row 0 must be judged against its state
    // before the request, not after the first update.
    $component->update([], ['products.0' => $products[0], 'products.1' => $products[1]]);

    expect($component->get('products.0.amount'))->toEqual(4500)
        ->and($component->get('products.1.amount'))->toEqual(14000);
});

/*
 * The services tax every line at its product's rate when they save, and the
 * header is summed from those lines - so the form has to use the same rate or
 * it shows one total and saves another.
 */

test('a wholesale update taxes rows at the product rate the document will be saved with', function () {
    $this->actingAsUser();

    $quote = quoteWithLines();
    // GST goes up after the quote was saved at 10%.
    TaxRate::where('name', 'GST')->update(['rate' => 20]);

    $component = Livewire::test(ModelProducts::class, ['model' => $quote]);

    $products = $component->get('products');
    $products[1]['quantity'] = 2;

    $component->set('products', $products);

    expect($component->get('products.0.tax_amount'))->toEqual(2700)
        ->and($component->get('products.1.tax_amount'))->toEqual(1400);

    $component->assertSet('sub_total', 20500.0)
        ->assertSet('tax', 4100.0);
});

test('a row with no product record is taxed at the default rate', function () {
    $this->actingAsUser();

    // A dynamic product typed in by name: the service creates it with no rate
    // of its own, so the line is saved at the default.
    TaxRate::create(['name' => 'Default', 'rate' => 10, 'default' => true]);

    $component = Livewire::test(ModelProducts::class);

    $component->set('products.0.id', 'Widget');
    $component->set('products.0.unit_price', '100.00');
    $component->set('products.0.quantity', 1);

    expect($component->get('products.0.tax_amount'))->toEqual(10);
    $component->assertSet('tax', 10.0);
});

test('an invoice form opens with its lines priced as they will be saved', function () {
    $this->actingAsUser();

    // A stored amount out of step with price x quantity. The form used to
    // show 300 and the save then stored 200 without a word.
    $product = Product::create(['name' => 'Widget']);
    $invoice = Invoice::create(['currency' => 'USD']);
    $invoice->invoiceLines()->create([
        'product_id' => $product->id, 'quantity' => 2, 'price' => 100, 'amount' => 300,
    ]);

    Livewire::test(ModelProducts::class, ['model' => $invoice->fresh(), 'creating' => 'Invoice'])
        ->assertSet('products.0.amount', 200.0)
        ->assertSet('sub_total', 200.0);
});

/*
 * A paid invoice opened for editing. InvoiceService keeps an unchanged line's
 * saved amount and tax once a payment is recorded, so the form shows the
 * saved figures - not a repricing at the product's current rate that the
 * save would not make.
 */

function paidInvoiceAtOldRate(): array
{
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $product = Product::create(['name' => 'Taxed', 'tax_rate_id' => $gst->id]);

    $invoice = Invoice::create(['currency' => 'USD', 'total' => 220, 'amount_paid' => 220, 'amount_due' => 0, 'fully_paid_at' => now()]);
    $invoice->invoiceLines()->create([
        'product_id' => $product->id, 'quantity' => 2, 'price' => 100,
        'amount' => 200, 'tax_rate' => 10, 'tax_amount' => 20,
    ]);

    $gst->update(['rate' => 15]);

    return [$invoice->fresh(), $product];
}

test('a paid invoice form opens with the pricing its lines were saved at', function () {
    $this->actingAsUser();

    [$invoice] = paidInvoiceAtOldRate();

    Livewire::test(ModelProducts::class, ['model' => $invoice, 'creating' => 'Invoice'])
        ->assertSet('products.0.tax_amount', 20.0)
        ->assertSet('products.0.tax_rate', 10.0)
        ->assertSet('tax', 20.0)
        ->assertSet('total', 220.0);
});

test('a comment on a paid invoice line leaves its saved pricing', function () {
    $this->actingAsUser();

    [$invoice] = paidInvoiceAtOldRate();

    $component = Livewire::test(ModelProducts::class, ['model' => $invoice, 'creating' => 'Invoice']);

    $component->set('products.0.comments', 'Noted');

    $component->assertSet('products.0.tax_amount', 20.0)
        ->assertSet('total', 220.0);
});

test('a quantity change on a paid invoice line is repriced, as the service will', function () {
    $this->actingAsUser();

    [$invoice] = paidInvoiceAtOldRate();

    $component = Livewire::test(ModelProducts::class, ['model' => $invoice, 'creating' => 'Invoice']);

    $component->set('products.0.quantity', 3);

    // 3 x 100.00 at the product's current 15% - the save will then be refused.
    $component->assertSet('products.0.amount', 300.0)
        ->assertSet('products.0.tax_amount', 45.0);
});

test('a quantity put back where it was on a paid invoice line shows its saved pricing', function () {
    $this->actingAsUser();

    // The service judges a line against what was saved, so the form does
    // too - judged against the start of each request, the quantity going
    // back to 2 read as a change and the row was repriced at the new rate.
    [$invoice] = paidInvoiceAtOldRate();

    $component = Livewire::test(ModelProducts::class, ['model' => $invoice, 'creating' => 'Invoice']);

    $component->set('products.0.quantity', 3);
    $component->set('products.0.quantity', 2);

    $component->assertSet('products.0.amount', 200.0)
        ->assertSet('products.0.tax_amount', 20.0)
        ->assertSet('products.0.tax_rate', 10.0)
        ->assertSet('total', 220.0);
});

test('a variation at the saved price on a paid invoice line keeps its saved pricing', function () {
    $this->actingAsUser();

    [$invoice, $product] = paidInvoiceAtOldRate();
    // No price of its own, so the row stays at the product's 100.00 - which
    // is the saved price, so the service keeps the line as saved.
    $large = $product->productVariations()->create(['name' => 'Large']);
    $product->productPrices()->create(['unit_price' => 100, 'currency' => 'USD']);

    $component = Livewire::test(ModelProducts::class, ['model' => $invoice, 'creating' => 'Invoice', 'currency' => 'USD']);

    $component->set('products.0.product_variation_id', $large->id);

    $component->assertSet('products.0.product_variation_id', $large->id)
        ->assertSet('products.0.tax_amount', 20.0)
        ->assertSet('total', 220.0);
});

test('the saved product picked again on a paid invoice line keeps its saved pricing', function () {
    $this->actingAsUser();

    // Picked away and back, the row is the saved line again - which the
    // service keeps as saved, so the form shows that rather than a
    // repricing at the product's current rate.
    [$invoice, $product] = paidInvoiceAtOldRate();
    $product->productPrices()->create(['unit_price' => 100, 'currency' => 'USD']);
    $other = Product::create(['name' => 'Other']);
    $other->productPrices()->create(['unit_price' => 50, 'currency' => 'USD']);

    $component = Livewire::test(ModelProducts::class, ['model' => $invoice, 'creating' => 'Invoice', 'currency' => 'USD']);

    $component->set('products.0.id', $other->id);
    $component->set('products.0.id', $product->id);

    $component->assertSet('products.0.unit_price', 100.0)
        ->assertSet('products.0.tax_amount', 20.0)
        ->assertSet('products.0.tax_rate', 10.0)
        ->assertSet('total', 220.0);
});

/**
 * ORD-2071's line - 2.5 x 333.33, GST 10%: 833.33 and 83.33 tax - with 1.25
 * of it invoiced at 416.66 and 41.67 tax.
 *
 * @return array{0: Order, 1: Invoice}
 */
function orderLineHalfInvoiced(): array
{
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $product = Product::create(['name' => 'Panel', 'tax_rate_id' => $gst->id]);

    $order = Order::create(['currency' => 'USD']);
    $orderProduct = $order->orderProducts()->create([
        'product_id' => $product->id, 'quantity' => 2.5, 'price' => 333.33,
        'tax_rate' => 10, 'tax_amount' => 8333, 'amount' => 833.33,
    ]);

    $invoice = Invoice::create(['order_id' => $order->id, 'currency' => 'USD']);
    $invoice->invoiceLines()->create([
        'product_id' => $product->id, 'order_product_id' => $orderProduct->id,
        'quantity' => 1.25, 'price' => 333.33, 'tax_rate' => 10, 'amount' => 416.66, 'tax_amount' => 41.67,
    ]);

    return [$order->fresh(), $invoice];
}

test('invoicing the rest of an order line bills exactly what is left of it', function () {
    $this->actingAsUser();

    [$order] = orderLineHalfInvoiced();

    // 1.25 x 333.33 alone is 416.66 and 41.67 tax, a cent short of the
    // order line once added to the first invoice.
    $component = Livewire::test(ModelProducts::class, [
        'model' => $order, 'creating' => 'Invoice', 'from' => 'Order',
    ]);

    $component->assertSet('products.0.quantity', 1.25)
        ->assertSet('products.0.amount', 416.67)
        ->assertSet('products.0.tax_amount', 41.66)
        ->assertSet('sub_total', 416.67)
        ->assertSet('tax', 41.66);

    // Less than the rest is priced on its own; the rest again is the remainder.
    $component->set('products.0.quantity', 1);
    $component->assertSet('products.0.amount', 333.33);

    $component->set('products.0.quantity', 1.25);
    $component->assertSet('products.0.amount', 416.67)
        ->assertSet('products.0.tax_amount', 41.66);
});

test('editing the invoice that took the rest of an order line keeps it at the remainder', function () {
    $this->actingAsUser();

    [$order, $first] = orderLineHalfInvoiced();

    $second = Invoice::create(['order_id' => $order->id, 'currency' => 'USD']);
    $second->invoiceLines()->create([
        'product_id' => $first->invoiceLines()->first()->product_id,
        'order_product_id' => $order->orderProducts()->first()->id,
        'quantity' => 1.25, 'price' => 333.33, 'tax_rate' => 10, 'amount' => 416.67, 'tax_amount' => 41.66,
    ]);

    Livewire::test(ModelProducts::class, ['model' => $second->fresh(), 'creating' => 'Invoice'])
        ->assertSet('products.0.amount', 416.67)
        ->assertSet('products.0.tax_amount', 41.66);

    // The first invoice is not the one that took the rest.
    Livewire::test(ModelProducts::class, ['model' => $first->fresh(), 'creating' => 'Invoice'])
        ->assertSet('products.0.amount', 416.66)
        ->assertSet('products.0.tax_amount', 41.67);
});
