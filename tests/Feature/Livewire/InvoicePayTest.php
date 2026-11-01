<?php

/*
 * Paying an invoice, in full or in part.
 *
 * A new invoice never had amount_due set, and the Pay drawer validated the
 * amount with `size:` the amount due - so on a new invoice only $0 passed, a
 * part payment was always refused, and pay() then subtracted amount_paid
 * twice.
 */

use Illuminate\Http\Request;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoicePay;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Services\InvoiceService;

beforeEach(function () {
    $this->actingAsUserWithPermissions(['view crm invoices', 'edit crm invoices']);
});

/**
 * A $220.00 invoice (2 x $100 + 10% tax) made the way the form makes one.
 */
function payableInvoice(): Invoice
{
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $product = Product::create(['name' => 'Taxed', 'tax_rate_id' => $gst->id]);

    return app(InvoiceService::class)->create(new Request([
        'currency' => 'USD',
        'products' => [['id' => $product->id, 'quantity' => 2, 'unit_price' => 100, 'amount' => 200, 'comments' => null]],
    ]))->fresh();
}

test('a new invoice is due in full', function () {
    $invoice = payableInvoice();

    expect((int) $invoice->getRawOriginal('total'))->toBe(22000)
        ->and((int) $invoice->getRawOriginal('amount_due'))->toBe(22000)
        ->and((int) $invoice->getRawOriginal('amount_paid'))->toBe(0);
});

test('a part payment lowers the amount due and leaves the invoice open', function () {
    $invoice = payableInvoice();

    Livewire::test(InvoicePay::class, ['invoice' => $invoice])
        ->set('amount', '100.00')
        ->call('pay')
        ->assertHasNoErrors();

    $invoice->refresh();

    expect((int) $invoice->getRawOriginal('amount_paid'))->toBe(10000)
        ->and((int) $invoice->getRawOriginal('amount_due'))->toBe(12000)
        ->and($invoice->fully_paid_at)->toBeNull();
});

test('paying the rest marks the invoice paid', function () {
    $invoice = payableInvoice();

    Livewire::test(InvoicePay::class, ['invoice' => $invoice])
        ->set('amount', '100.00')
        ->call('pay')
        ->assertHasNoErrors();

    // A fresh mount, as the page would after a reload, offers the balance.
    Livewire::test(InvoicePay::class, ['invoice' => $invoice->fresh()])
        ->assertSet('amount', 120)
        ->set('amount', '120.00')
        ->call('pay')
        ->assertHasNoErrors();

    $invoice->refresh();

    expect((int) $invoice->getRawOriginal('amount_paid'))->toBe(22000)
        ->and((int) $invoice->getRawOriginal('amount_due'))->toBe(0)
        ->and($invoice->fully_paid_at)->not->toBeNull();
});

test('a masked amount with a thousands separator is accepted', function () {
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $product = Product::create(['name' => 'Big', 'tax_rate_id' => $gst->id]);

    $invoice = app(InvoiceService::class)->create(new Request([
        'currency' => 'USD',
        'products' => [['id' => $product->id, 'quantity' => 1, 'unit_price' => 2000, 'amount' => 2000, 'comments' => null]],
    ]))->fresh();

    Livewire::test(InvoicePay::class, ['invoice' => $invoice])
        ->set('amount', '1,500.00')
        ->call('pay')
        ->assertHasNoErrors();

    expect((int) $invoice->fresh()->getRawOriginal('amount_due'))->toBe(70000);
});

test('a payment of nothing is refused', function () {
    $invoice = payableInvoice();

    Livewire::test(InvoicePay::class, ['invoice' => $invoice])
        ->set('amount', '0.00')
        ->call('pay')
        ->assertHasErrors('amount');

    expect((int) $invoice->fresh()->getRawOriginal('amount_paid'))->toBe(0);
});

test('an overpayment is refused', function () {
    $invoice = payableInvoice();

    Livewire::test(InvoicePay::class, ['invoice' => $invoice])
        ->set('amount', '220.01')
        ->call('pay')
        ->assertHasErrors('amount');

    expect((int) $invoice->fresh()->getRawOriginal('amount_paid'))->toBe(0);
});

test('the Pay button shows on an open invoice and goes once it is paid', function () {
    $invoice = payableInvoice();

    Livewire::test(InvoicePay::class, ['invoice' => $invoice])
        ->assertSee('wire:submit="pay"', false)
        ->set('amount', '220.00')
        ->call('pay')
        ->assertDontSee('wire:submit="pay"', false);

    Livewire::test(InvoicePay::class, ['invoice' => $invoice->fresh()])
        ->assertDontSee('wire:submit="pay"', false);
});
