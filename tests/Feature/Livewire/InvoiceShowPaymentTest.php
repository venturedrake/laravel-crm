<?php

use Carbon\Carbon;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoicePay;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceShow;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Setting;

beforeEach(function () {
    Carbon::setTestNow('2026-06-15 12:00:00');

    // The real show view mounts the Send drawer, whose subject needs this setting.
    Setting::query()->delete();
    app('laravel-crm.settings')->set('organization_name', 'Test Organization');
    app('laravel-crm.settings')->forgetCache();

    $this->actingAsUserWithPermissions(['view crm invoices', 'edit crm invoices']);
});

afterEach(function () {
    Carbon::setTestNow();
});

function showPaymentInvoice(array $attributes = []): Invoice
{
    return Invoice::create(array_merge([
        'currency' => 'USD',
        'subtotal' => 15465.97,
        'tax' => 0,
        'total' => 15465.97,
        'amount_paid' => 5000.50,
        'due_date' => '2026-06-16',
    ], $attributes))->fresh();
}

// Check the actual totals cells, not amounts in the header or nested Pay drawer.
function assertInvoicePaymentRows($page, string $paid, string $due): void
{
    $document = new DOMDocument;
    @$document->loadHTML($page->html());
    $xpath = new DOMXPath($document);

    foreach (['Total' => '$15,465.97', 'Amount paid' => $paid, 'Balance due' => $due] as $label => $amount) {
        $cells = $xpath->query('//tfoot/tr/td[strong[normalize-space(.)="'.$label.'"]]/following-sibling::td');
        expect($cells->length)->toBe(1);
        expect(trim($cells->item(0)->textContent))->toBe($amount);
    }

    $page->assertSeeInOrder(['Amount paid', $paid, 'Balance due', $due]);
}

test('the invoice show page displays a partial payment and retains its due badge', function () {
    $page = Livewire::test(InvoiceShow::class, ['invoice' => showPaymentInvoice()])
        ->assertOk()
        ->assertSee('Part paid')
        ->assertSee('Due tomorrow');

    assertInvoicePaymentRows($page, '$5,000.50', '$10,465.47');
});

test('an unpaid invoice hides the payment rows and part paid badge', function () {
    Livewire::test(InvoiceShow::class, ['invoice' => showPaymentInvoice(['amount_paid' => 0])])
        ->assertOk()
        ->assertDontSee('Amount paid')
        ->assertDontSee('Balance due')
        ->assertDontSee('Part paid')
        ->assertSee('Due tomorrow');
});

test('a fully paid invoice displays zero balance and the Paid badge', function (float $paid, string $formatted) {
    $page = Livewire::test(InvoiceShow::class, ['invoice' => showPaymentInvoice([
        'amount_paid' => $paid,
        'fully_paid_at' => now(),
    ])])
        ->assertOk()
        ->assertSee('Paid')
        ->assertSeeHtml('badge-success')
        ->assertDontSee('Part paid')
        ->assertDontSee('Due tomorrow');

    assertInvoicePaymentRows($page, $formatted, '$0.00');
})->with([
    'settled' => [15465.97, '$15,465.97'],
    'legacy paid flag without a recorded amount' => [0.0, '$0.00'],
]);

test('a partial payment can still be overdue', function () {
    $page = Livewire::test(InvoiceShow::class, ['invoice' => showPaymentInvoice(['due_date' => '2026-06-01'])])
        ->assertOk()
        ->assertSee('Part paid')
        ->assertSee('Overdue');

    assertInvoicePaymentRows($page, '$5,000.50', '$10,465.47');
});

test('a partial payment renders without a due date', function () {
    $page = Livewire::test(InvoiceShow::class, ['invoice' => showPaymentInvoice(['due_date' => null])])
        ->assertOk()
        ->assertSee('Part paid')
        ->assertDontSee('Overdue')
        ->assertDontSeeHtml('badge-secondary');

    assertInvoicePaymentRows($page, '$5,000.50', '$10,465.47');
});

test('the displayed balance ignores a stale stored amount due', function () {
    $page = Livewire::test(InvoiceShow::class, ['invoice' => showPaymentInvoice(['amount_due' => 15465.97])])
        ->assertOk();

    assertInvoicePaymentRows($page, '$5,000.50', '$10,465.47');
});

test('the displayed balance never becomes negative', function () {
    $page = Livewire::test(InvoiceShow::class, ['invoice' => showPaymentInvoice(['amount_paid' => 16000])])
        ->assertOk();

    assertInvoicePaymentRows($page, '$16,000.00', '$0.00');
});

test('a mounted invoice refreshes after partial and final payments just like a fresh page', function () {
    $invoice = showPaymentInvoice(['amount_paid' => 0]);
    $page = Livewire::test(InvoiceShow::class, ['invoice' => $invoice])
        ->assertOk()
        ->assertDontSee('Amount paid');

    Livewire::test(InvoicePay::class, ['invoice' => $invoice->fresh()])
        ->set('amount', '5000.50')
        ->call('pay')
        ->assertHasNoErrors()
        ->assertDispatched('refreshInvoice');

    $page->assertDontSee('Amount paid');
    $page->dispatch('refreshInvoice')->assertSee('Part paid')->assertSee('Due tomorrow');
    assertInvoicePaymentRows($page, '$5,000.50', '$10,465.47');

    $freshPage = Livewire::test(InvoiceShow::class, ['invoice' => $invoice->fresh()])
        ->assertOk()->assertSee('Part paid')->assertSee('Due tomorrow');
    assertInvoicePaymentRows($freshPage, '$5,000.50', '$10,465.47');

    Livewire::test(InvoicePay::class, ['invoice' => $invoice->fresh()])
        ->set('amount', '10465.47')
        ->call('pay')
        ->assertHasNoErrors()
        ->assertDispatched('refreshInvoice');

    $page->assertSee('Part paid');
    $page->dispatch('refreshInvoice')
        ->assertSee('Paid')->assertSeeHtml('badge-success')
        ->assertDontSee('Part paid')->assertDontSee('Due tomorrow');
    assertInvoicePaymentRows($page, '$15,465.97', '$0.00');

    $freshPage = Livewire::test(InvoiceShow::class, ['invoice' => $invoice->fresh()])
        ->assertOk()->assertSee('Paid')->assertSeeHtml('badge-success')
        ->assertDontSee('Part paid')->assertDontSee('Due tomorrow');
    assertInvoicePaymentRows($freshPage, '$15,465.97', '$0.00');
});
