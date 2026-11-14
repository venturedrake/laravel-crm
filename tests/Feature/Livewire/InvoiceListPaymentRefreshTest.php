<?php

/*
 * #136: paying an invoice from a list left the row stale.
 *
 * The Pay drawer on each invoice row is its own component. It saved the
 * payment and dispatched `refreshInvoice`, but only the show page listened,
 * so the invoice index and the related-invoices table (organization, person
 * and order pages) kept the old Paid date, Paid, Due and Overdue cells and
 * the old Edit/Delete actions until the page was reloaded.
 *
 * Each test first asserts the stale row (the payment has landed but nothing
 * has told the table), then dispatches the event and asserts the fresh one,
 * so it is the listener that does the work.
 */

use Carbon\Carbon;
use Illuminate\Http\Request;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceIndex;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoicePay;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceRelatedIndex;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Services\InvoiceService;

beforeEach(function () {
    // Paid on a day that is neither the issue date nor the due date, so the
    // paid-date cell can be told apart from them.
    Carbon::setTestNow('2026-06-15 12:00:00');

    // The row actions mount the Send drawer, whose subject blade reads the
    // organization_name setting unguarded.
    Setting::query()->delete();
    app('laravel-crm.settings')->set('organization_name', 'Test Organization');
    app('laravel-crm.settings')->forgetCache();

    $this->actingAsUserWithPermissions([
        'view crm invoices',
        'edit crm invoices',
        'delete crm invoices',
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * A $220.00 invoice (2 x $100 + 10% tax) for an organization, made the way
 * the form makes one, two weeks overdue.
 */
function listRefreshPayableInvoice(Organization $organization): Invoice
{
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $product = Product::create(['name' => 'Taxed', 'tax_rate_id' => $gst->id]);

    return app(InvoiceService::class)->create(new Request([
        'currency' => 'USD',
        'issue_date' => '2026-05-01',
        'due_date' => '2026-05-31',
        'products' => [['id' => $product->id, 'quantity' => 2, 'unit_price' => 100, 'amount' => 200, 'comments' => null]],
    ]), null, $organization)->fresh();
}

function listRefreshPay(Invoice $invoice, string $amount): void
{
    Livewire::test(InvoicePay::class, ['invoice' => $invoice->fresh()])
        ->set('amount', $amount)
        ->call('pay')
        ->assertHasNoErrors();
}

test('paying an invoice tells the other components to refresh', function () {
    $invoice = listRefreshPayableInvoice(Organization::create(['name' => 'Acme']));

    Livewire::test(InvoicePay::class, ['invoice' => $invoice])
        ->set('amount', '100.00')
        ->call('pay')
        ->assertHasNoErrors()
        ->assertDispatched('refreshInvoice');
});

test('an invoice list refreshes its row after a part payment and after the final payment', function (string $list) {
    $organization = Organization::create(['name' => 'Acme']);
    $invoice = listRefreshPayableInvoice($organization);
    $editUrl = route('laravel-crm.invoices.edit', $invoice);

    $table = $list === InvoiceRelatedIndex::class
        ? Livewire::test($list, ['model' => $organization])
        : Livewire::test($list);

    $table->assertOk()
        ->assertSee('$0.00')          // paid
        ->assertSee('2 weeks ago')    // overdue
        ->assertSeeHtml($editUrl)
        ->assertDontSee('2026-06-15');

    // A part payment: the table has not heard about it yet.
    listRefreshPay($invoice, '100.00');

    $table->assertSee('$0.00')
        ->assertDontSee('$100.00')
        ->assertDontSee('$120.00')
        ->assertSeeHtml($editUrl);

    $table->dispatch('refreshInvoice')
        ->assertSee('$100.00')        // paid
        ->assertSee('$120.00')        // due
        ->assertDontSee('$0.00')
        ->assertSee('2 weeks ago')    // still overdue
        ->assertDontSee('2026-06-15') // not paid in full
        ->assertDontSeeHtml($editUrl); // nothing paid can be edited or deleted

    // The rest.
    listRefreshPay($invoice, '120.00');

    $table->assertSee('$120.00')
        ->assertDontSee('2026-06-15');

    $table->dispatch('refreshInvoice')
        ->assertSee('$220.00')        // paid (and total)
        ->assertSee('$0.00')          // due
        ->assertDontSee('$100.00')
        ->assertDontSee('$120.00')
        ->assertSee('2026-06-15')     // paid date
        ->assertDontSee('2 weeks ago'); // no longer overdue
})->with([
    'invoice index' => InvoiceIndex::class,
    'related invoices' => InvoiceRelatedIndex::class,
]);

test('both invoice lists listen for the payment event', function (string $component) {
    $listeners = (fn () => $this->getListeners())->call(new $component);

    expect($listeners)->toHaveKey('refreshInvoice', '$refresh');
})->with([
    // Keyed: a bare two-string list is read as a [class, method] callable.
    'invoice index' => InvoiceIndex::class,
    'related invoices' => InvoiceRelatedIndex::class,
]);
