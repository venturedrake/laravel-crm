<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Services\InvoiceService;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

beforeEach(function () {
    $this->actingAsUser();
});

test('service creates an invoice with minimum data', function () {
    // The header totals come from the saved lines.
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $product = Product::create(['name' => 'Taxed', 'tax_rate_id' => $gst->id]);

    $invoice = app(InvoiceService::class)->create(new Request([
        'reference' => 'INV-1', 'currency' => 'USD',
        'issue_date' => '2025-01-01', 'due_date' => '2025-01-31',
        'sub_total' => 100, 'tax' => 10, 'total' => 110,
        'products' => [['id' => $product->id, 'quantity' => 1, 'unit_price' => 100, 'amount' => 100, 'comments' => null]],
    ]));

    expect($invoice)->toBeInstanceOf(Invoice::class);
    expect($invoice->reference)->toBe('INV-1');
    expect((int) $invoice->fresh()->getRawOriginal('total'))->toBe(11000);
});

test('service attaches person org and order', function () {
    $person = Person::create(['first_name' => 'C']);
    $org = Organization::create(['name' => 'Acme']);
    $order = Order::create(['description' => 'Order']);

    $invoice = app(InvoiceService::class)->create(new Request([
        'currency' => 'USD',
        'order_id' => $order->id,
    ]), $person, $org);

    expect($invoice->person_id)->toBe($person->id);
    expect($invoice->organization_id)->toBe($org->id);
    expect($invoice->order_id)->toBe($order->id);
});

test('service updates an invoice', function () {
    $invoice = Invoice::create(['currency' => 'USD']);

    app(InvoiceService::class)->update(new Request([
        'reference' => 'NEW', 'currency' => 'GBP',
        'sub_total' => 1, 'tax' => 0, 'total' => 1,
    ]), $invoice);

    $fresh = $invoice->fresh();
    expect($fresh->reference)->toBe('NEW');
    expect($fresh->currency)->toBe('GBP');
});

test('service keeps the tax on existing lines when the invoice is updated', function () {
    // Created first so it holds the id the existing line will get - the lookup
    // used to find the product by the line's id and land on this one, untaxed.
    Product::create(['name' => 'Decoy']);

    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $product = Product::create(['name' => 'Taxed', 'tax_rate_id' => $gst->id]);

    $invoice = Invoice::create(['currency' => 'USD']);
    $existing = $invoice->invoiceLines()->create([
        'product_id' => $product->id, 'quantity' => 1, 'price' => 1200,
        'amount' => 1200, 'tax_rate' => 10, 'tax_amount' => 120, 'currency' => 'USD',
    ]);

    expect($existing->id)->not->toBe($product->id);

    app(InvoiceService::class)->update(new Request([
        'currency' => 'USD', 'sub_total' => 1800, 'tax' => 180, 'total' => 1980,
        'products' => [
            ['invoice_line_id' => $existing->id, 'id' => $product->id, 'quantity' => 1, 'unit_price' => '1,200', 'amount' => '1,200', 'comments' => null],
            ['id' => $product->id, 'quantity' => 1, 'unit_price' => '600', 'amount' => '600', 'comments' => null],
        ],
    ]), $invoice);

    $lines = $invoice->invoiceLines()->orderBy('order')->get();

    expect($lines)->toHaveCount(2);
    expect((float) $lines[0]->tax_rate)->toBe(10.0);
    expect((int) $lines[0]->getRawOriginal('tax_amount'))->toBe(12000);
    expect((float) $lines[1]->tax_rate)->toBe(10.0);
    expect((int) $lines[1]->getRawOriginal('tax_amount'))->toBe(6000);
});

/*
 * Line amounts are priced from the unit price and quantity. The request's
 * amount is only what the form last showed - a follow-up invoice from an
 * order used to post the order line's full amount against a part quantity.
 */

test('service prices a new line from its unit price and quantity', function () {
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $product = Product::create(['name' => 'Taxed', 'tax_rate_id' => $gst->id]);

    $invoice = app(InvoiceService::class)->create(new Request([
        'currency' => 'USD',
        'products' => [
            // 3 of 5 left to invoice, still carrying the order line's 5 x 2000.
            ['id' => $product->id, 'quantity' => 3, 'unit_price' => '2,000.00', 'amount' => '10,000.00', 'comments' => null],
            ['id' => $product->id, 'quantity' => 1.25, 'unit_price' => '333.20', 'amount' => '833.00', 'comments' => null],
        ],
    ]));

    $lines = $invoice->invoiceLines()->orderBy('order')->get();

    expect($lines->pluck('amount')->all())->toBe([600000, 41650])
        ->and($lines->pluck('tax_amount')->all())->toBe([60000, 4165]);
});

test('service reprices an existing line and a line added on update', function () {
    $product = Product::create(['name' => 'Plain']);

    $invoice = Invoice::create(['currency' => 'USD']);
    $existing = $invoice->invoiceLines()->create([
        'product_id' => $product->id, 'quantity' => 1, 'price' => 100,
        'amount' => 100, 'tax_rate' => 0, 'tax_amount' => 0, 'currency' => 'USD',
    ]);

    app(InvoiceService::class)->update(new Request([
        'currency' => 'USD',
        'products' => [
            ['invoice_line_id' => $existing->id, 'id' => $product->id, 'quantity' => 2.5, 'unit_price' => '333.33', 'amount' => '999.99', 'comments' => null],
            ['id' => $product->id, 'quantity' => 3.5, 'unit_price' => '9.99', 'amount' => '1.00', 'comments' => null],
        ],
    ]), $invoice);

    expect($invoice->invoiceLines()->orderBy('order')->pluck('amount')->all())->toBe([83333, 3497]);
});

/*
 * Editing an invoice after a payment. The form hides Edit once anything is
 * paid; the API and the edit URL do not, so the service fixes the total
 * itself. Moving it under a payment left a part-paid invoice nothing could
 * close, or reopened a paid one and lost the date it was paid on.
 */

function invoiceLinesRequest(Product $product, $unitPrice, array $extra = []): Request
{
    return new Request($extra + [
        'currency' => 'USD',
        'products' => [
            ['id' => $product->id, 'quantity' => 1, 'unit_price' => $unitPrice, 'amount' => $unitPrice, 'comments' => null],
        ],
    ]);
}

/**
 * A $200 invoice with $150 of it paid.
 */
function partPaidInvoice(Product $product): Invoice
{
    $invoice = app(InvoiceService::class)->create(invoiceLinesRequest($product, 200));
    $invoice->update(['amount_paid' => 150, 'amount_due' => 50]);

    return $invoice->fresh();
}

test('a part-paid invoice cannot have its total changed', function () {
    $product = Product::create(['name' => 'Plain']);
    $invoice = partPaidInvoice($product);

    expect(fn () => app(InvoiceService::class)->update(invoiceLinesRequest($product, 100), $invoice))
        ->toThrow(ValidationException::class);

    // Rolled back whole: lines, total and balance as they were.
    $invoice->refresh();

    expect((int) $invoice->getRawOriginal('total'))->toBe(20000)
        ->and((int) $invoice->getRawOriginal('amount_due'))->toBe(5000)
        ->and($invoice->invoiceLines()->sole()->amount)->toBe(20000);
});

test('a part-paid invoice still saves an edit that keeps its total', function () {
    $product = Product::create(['name' => 'Plain']);
    $invoice = partPaidInvoice($product);

    app(InvoiceService::class)->update(invoiceLinesRequest($product, 200, ['reference' => 'PO-77']), $invoice);

    $invoice->refresh();

    expect($invoice->reference)->toBe('PO-77')
        ->and((int) $invoice->getRawOriginal('amount_due'))->toBe(5000)
        ->and($invoice->fully_paid_at)->toBeNull();
});

test('a paid invoice keeps the date it was paid on through an edit', function () {
    $product = Product::create(['name' => 'Plain']);
    $paidAt = now()->subMonths(3)->startOfSecond();

    $invoice = app(InvoiceService::class)->create(invoiceLinesRequest($product, 100));
    $invoice->update(['amount_paid' => 100, 'amount_due' => 0, 'fully_paid_at' => $paidAt]);

    expect(fn () => app(InvoiceService::class)->update(invoiceLinesRequest($product, 250), $invoice->fresh()))
        ->toThrow(ValidationException::class);

    app(InvoiceService::class)->update(invoiceLinesRequest($product, 100, ['reference' => 'Fixed']), $invoice->fresh());

    $invoice->refresh();

    expect($invoice->fully_paid_at->equalTo($paidAt))->toBeTrue()
        ->and((int) $invoice->getRawOriginal('amount_due'))->toBe(0);
});

test('an invoice marked paid with nothing recorded stays paid through an edit', function () {
    // Paid through the old drawer, which only took 0 - before the migration
    // that settles these has run.
    $product = Product::create(['name' => 'Plain']);
    $invoice = app(InvoiceService::class)->create(invoiceLinesRequest($product, 100));
    $invoice->update(['amount_paid' => 0, 'amount_due' => 0, 'fully_paid_at' => now()]);

    app(InvoiceService::class)->update(invoiceLinesRequest($product, 100, ['reference' => 'Fixed']), $invoice->fresh());

    $invoice->refresh();

    expect($invoice->fully_paid_at)->not->toBeNull()
        ->and((int) $invoice->getRawOriginal('amount_due'))->toBe(0);
});

test('an unpaid invoice can still have its total changed', function () {
    $product = Product::create(['name' => 'Plain']);
    $invoice = app(InvoiceService::class)->create(invoiceLinesRequest($product, 200));

    app(InvoiceService::class)->update(invoiceLinesRequest($product, 100), $invoice);

    $invoice->refresh();

    expect((int) $invoice->getRawOriginal('total'))->toBe(10000)
        ->and((int) $invoice->getRawOriginal('amount_due'))->toBe(10000)
        ->and($invoice->fully_paid_at)->toBeNull();
});

test('a paid invoice whose stored total disagrees with its lines can still be edited', function () {
    // Before the header was derived from the lines, an API invoice stored
    // the tax the request sent (none) while its lines were taxed - so the
    // header is a cent or more off. The lock compares the lines before and
    // after the edit, not the header, and leaves the header as it was.
    $product = Product::create(['name' => 'Plain']);
    $invoice = app(InvoiceService::class)->create(invoiceLinesRequest($product, 100));
    $invoice->update(['total' => 99.99, 'amount_paid' => 99.99, 'amount_due' => 0, 'fully_paid_at' => now()]);

    app(InvoiceService::class)->update(invoiceLinesRequest($product, 100, ['reference' => 'Fixed']), $invoice->fresh());

    $invoice->refresh();

    expect($invoice->reference)->toBe('Fixed')
        ->and((int) $invoice->getRawOriginal('total'))->toBe(9999)
        ->and($invoice->fully_paid_at)->not->toBeNull();
});

test('a paid invoice with no stored total can still be edited', function () {
    $product = Product::create(['name' => 'Plain']);
    $invoice = app(InvoiceService::class)->create(invoiceLinesRequest($product, 100));
    $invoice->update(['total' => null, 'amount_paid' => 100, 'amount_due' => 0, 'fully_paid_at' => now()]);

    app(InvoiceService::class)->update(invoiceLinesRequest($product, 100, ['reference' => 'Fixed']), $invoice->fresh());

    expect($invoice->fresh()->reference)->toBe('Fixed');
});

test('a paid invoice refuses an edit that moves tax into the subtotal', function () {
    // The sum is unchanged, so a lock on the total alone let this through and
    // left the header carrying tax no line did.
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $taxed = Product::create(['name' => 'Taxed', 'tax_rate_id' => $gst->id]);
    $untaxed = Product::create(['name' => 'Untaxed']);

    $invoice = app(InvoiceService::class)->create(invoiceLinesRequest($taxed, 100));
    $invoice->update(['amount_paid' => 110, 'amount_due' => 0, 'fully_paid_at' => now()]);
    $lineId = $invoice->invoiceLines()->sole()->id;

    expect(fn () => app(InvoiceService::class)->update(new Request([
        'currency' => 'USD',
        'products' => [
            ['invoice_line_id' => $lineId, 'id' => $untaxed->id, 'quantity' => 1, 'unit_price' => 110, 'amount' => 110, 'comments' => null],
        ],
    ]), $invoice->fresh()))->toThrow(ValidationException::class);

    $invoice->refresh();

    expect((int) $invoice->getRawOriginal('tax'))->toBe(1000)
        ->and($invoice->invoiceLines()->sole()->product_id)->toBe($taxed->id);
});

test('a paid invoice saves its lines re-sent unchanged after the tax rate moved', function () {
    // The form re-sends every line. Repricing them at the product's current
    // rate had the lock refuse an edit to the terms once GST went up.
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $product = Product::create(['name' => 'Taxed', 'tax_rate_id' => $gst->id]);

    $invoice = app(InvoiceService::class)->create(invoiceLinesRequest($product, 100));
    $invoice->update(['amount_paid' => 110, 'amount_due' => 0, 'fully_paid_at' => now()]);
    $line = $invoice->invoiceLines()->sole();

    $gst->update(['rate' => 15]);

    app(InvoiceService::class)->update(new Request([
        'currency' => 'USD', 'terms' => 'Net 7',
        'products' => [
            ['invoice_line_id' => $line->id, 'id' => $product->id, 'quantity' => 1, 'unit_price' => '100.00', 'amount' => '100.00', 'comments' => 'Noted'],
        ],
    ]), $invoice->fresh());

    $invoice->refresh();
    $line->refresh();

    expect($invoice->terms)->toBe('Net 7')
        ->and($line->comments)->toBe('Noted')
        ->and($line->tax_amount)->toBe(1000)
        ->and((float) $line->tax_rate)->toBe(10.0)
        ->and((int) $invoice->getRawOriginal('tax'))->toBe(1000);
});

test('a paid invoice saves a line re-sent at a saved amount that is not price times quantity', function () {
    $product = Product::create(['name' => 'Plain']);

    $invoice = app(InvoiceService::class)->create(invoiceLinesRequest($product, 100));
    $line = $invoice->invoiceLines()->sole();
    // As the API stored a discounted line before amounts were derived.
    $line->update(['amount' => 80]);
    $invoice->update(['total' => 80, 'amount_paid' => 80, 'amount_due' => 0, 'fully_paid_at' => now()]);

    app(InvoiceService::class)->update(new Request([
        'currency' => 'USD', 'reference' => 'Fixed',
        'products' => [
            ['invoice_line_id' => $line->id, 'id' => $product->id, 'quantity' => 1, 'unit_price' => 100, 'amount' => 80, 'comments' => null],
        ],
    ]), $invoice->fresh());

    expect($invoice->fresh()->reference)->toBe('Fixed')
        ->and($line->fresh()->amount)->toBe(8000);
});

test('a line id from another invoice is not edited through this one', function () {
    $product = Product::create(['name' => 'Plain']);

    $other = app(InvoiceService::class)->create(invoiceLinesRequest($product, 100));
    $otherLine = $other->invoiceLines()->sole();

    $invoice = app(InvoiceService::class)->create(invoiceLinesRequest($product, 200));

    app(InvoiceService::class)->update(new Request([
        'currency' => 'USD',
        'products' => [
            ['invoice_line_id' => $otherLine->id, 'id' => $product->id, 'quantity' => 1, 'unit_price' => 999, 'amount' => 999, 'comments' => null],
        ],
    ]), $invoice);

    // The other invoice's line is untouched; this one got a new line instead.
    expect($otherLine->fresh()->amount)->toBe(10000)
        ->and($invoice->invoiceLines()->sole()->amount)->toBe(99900);
});

test('a line on a product that no longer exists is saved on a replacement product', function () {
    // addProduct() read the line's price under a key the form never sends.
    $product = Product::create(['name' => 'Gone']);
    $invoice = app(InvoiceService::class)->create(invoiceLinesRequest($product, 100));
    $line = $invoice->invoiceLines()->sole();
    $product->forceDelete();

    app(InvoiceService::class)->update(new Request([
        'currency' => 'USD',
        'products' => [
            ['invoice_line_id' => $line->id, 'id' => $product->id, 'quantity' => 1, 'unit_price' => 100, 'amount' => 100, 'comments' => null],
        ],
    ]), $invoice->fresh());

    $line->refresh();

    expect($line->product_id)->not->toBe($product->id)
        ->and($line->product->productPrices()->sole()->unit_price)->toBe(10000)
        ->and($line->amount)->toBe(10000);
});

/*
 * lockForEdit(): the row an edit or a payment works from is the one the lock
 * read, not a re-read - on MySQL a re-read after the lock can still see the
 * transaction's older snapshot. SQLite has neither row locks nor snapshots,
 * so these pin the contract rather than the race itself.
 */

test('the lock loads the row as stored now into the model being edited', function () {
    $invoice = Invoice::create(['currency' => 'USD', 'total' => 100, 'amount_paid' => 0, 'amount_due' => 100]);

    // A payment lands after the edit loaded its copy.
    DB::table(config('laravel-crm.db_table_prefix').'invoices')->where('id', $invoice->id)
        ->update(['amount_paid' => 10000, 'amount_due' => 0, 'fully_paid_at' => now()]);

    DB::transaction(fn () => app(InvoiceService::class)->lockForEdit($invoice));

    expect((int) $invoice->getRawOriginal('amount_paid'))->toBe(10000)
        ->and($invoice->fully_paid_at)->not->toBeNull()
        ->and($invoice->isDirty())->toBeFalse();
});

test('the lock finds the invoice after the user switches team', function () {
    // The open invoice is team 1's; the user has since switched to team 2 in
    // another tab. A scoped lock query found nothing and the payment 500ed.
    config()->set('laravel-crm.teams', true);

    $user = User::create([
        'name' => 'Team User', 'email' => 'lock-team-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'), 'crm_access' => true,
        'current_team_id' => 1, 'team_ids' => json_encode([1, 2]),
    ]);
    $this->actingAs($user);

    $invoice = Invoice::create(['currency' => 'USD', 'total' => 100, 'team_id' => 1]);

    $user->unsetRelation('currentTeam');
    $user->forceFill(['current_team_id' => 2])->save();

    expect(Invoice::whereKey($invoice->id)->first())->toBeNull();

    $locked = DB::transaction(fn () => app(InvoiceService::class)->lockForEdit($invoice));

    expect($locked->getKey())->toBe($invoice->id);

    config()->set('laravel-crm.teams', false);
});
