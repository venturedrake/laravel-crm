<?php

/*
 * The edit page by URL on an invoice with a payment recorded. Its Edit button
 * is hidden, but the route is not; the service refuses a total change, and
 * the form has no field for that error, so the page shows it as a toast
 * rather than failing silently.
 */

use Illuminate\Http\Request;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceEdit;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Services\InvoiceService;

beforeEach(function () {
    $this->actingAsUserWithPermissions(['view crm invoices', 'edit crm invoices']);
});

test('changing the total of a part-paid invoice is refused with a message', function () {
    $product = Product::create(['name' => 'Plain']);
    $organization = Organization::create(['name' => 'Customer Co']);

    $invoice = app(InvoiceService::class)->create(new Request([
        'currency' => 'USD',
        'products' => [['id' => $product->id, 'quantity' => 2, 'unit_price' => 100, 'amount' => 200, 'comments' => null]],
    ]), null, $organization);
    $invoice->update(['amount_paid' => 150, 'amount_due' => 50]);

    $component = Livewire::test(InvoiceEdit::class, ['invoice' => $invoice->fresh()])
        ->set('products', [['id' => $product->id, 'quantity' => 1, 'unit_price' => 100, 'amount' => 100, 'comments' => null]])
        ->call('save')
        ->assertNoRedirect();

    // Mary shows a toast through a js effect.
    expect(json_encode($component->effects['xjs'] ?? []))->toContain(ucfirst(__('laravel-crm::lang.invoice_total_locked_after_payment')))
        ->and((int) $invoice->fresh()->getRawOriginal('total'))->toBe(20000);
});
