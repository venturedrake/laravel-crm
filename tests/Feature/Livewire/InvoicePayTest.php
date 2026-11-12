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

/*
 * INV-2288: a part payment said "Invoice paid", though most of the invoice
 * was still owing.
 */
test('a part payment says how much is still due, not that the invoice is paid', function () {
    $invoice = payableInvoice();

    $component = Livewire::test(InvoicePay::class, ['invoice' => $invoice])
        ->set('amount', '100.00')
        ->call('pay')
        ->assertHasNoErrors();

    // Mary shows a toast through a js effect.
    expect(json_encode($component->effects['xjs'] ?? []))->toContain('Payment of $100.00 recorded. $120.00 still due.')
        ->not->toContain(ucfirst(__('laravel-crm::lang.invoice_paid')));
});

test('the payment that clears the balance says the invoice is paid', function () {
    $invoice = payableInvoice();

    Livewire::test(InvoicePay::class, ['invoice' => $invoice])
        ->set('amount', '100.00')
        ->call('pay');

    $component = Livewire::test(InvoicePay::class, ['invoice' => $invoice->fresh()])
        ->set('amount', '120.00')
        ->call('pay')
        ->assertHasNoErrors();

    expect(json_encode($component->effects['xjs'] ?? []))->toContain(ucfirst(__('laravel-crm::lang.invoice_paid')))
        ->not->toContain('still due');
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

/*
 * Issue #127: an overpayment showed Laravel's default text with the balance
 * as a bare number - "The amount field must be less than or equal to 6349.54."
 */
test('an overpayment says how much is still due, formatted as money', function () {
    $invoice = payableInvoice();

    $component = Livewire::test(InvoicePay::class, ['invoice' => $invoice])
        ->set('amount', '220.01')
        ->call('pay')
        ->assertHasErrors(['amount' => 'lte']);

    expect($component->errors()->first('amount'))
        ->toBe('The payment can be no more than the $220.00 still due')
        ->not->toContain('less than or equal');
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

/*
 * Two payments at once. validate() checks the amount against the balance
 * read before the row lock, so two tabs paying the last 100.00 both passed
 * it, and the second wrote an overpayment - a negative balance, which the
 * model then reads as the whole total still due. The amount is checked
 * again against the locked row.
 */

/**
 * The Pay drawer as a second tab sees it: validation works from the balance
 * it loaded, before another tab's payment landed.
 */
class StaleInvoicePay extends InvoicePay
{
    public int $staleOutstanding = 0;

    protected function rules()
    {
        return ['amount' => 'required|numeric|gt:0|lte:'.($this->staleOutstanding / 100)];
    }
}

test('a payment that another payment beat to the balance is refused', function () {
    $invoice = payableInvoice();

    $component = Livewire::test(StaleInvoicePay::class, ['invoice' => $invoice])
        ->set('staleOutstanding', 22000)
        ->set('amount', '220.00');

    // The other tab pays in full first.
    $invoice->update(['amount_paid' => 220, 'amount_due' => 0, 'fully_paid_at' => now()->subMinute()]);
    $paidAt = $invoice->fresh()->fully_paid_at;

    $component->call('pay');

    $invoice->refresh();

    expect((int) $invoice->getRawOriginal('amount_paid'))->toBe(22000)
        ->and((int) $invoice->getRawOriginal('amount_due'))->toBe(0)
        ->and($invoice->fully_paid_at->equalTo($paidAt))->toBeTrue()
        ->and(json_encode($component->effects['xjs'] ?? []))->toContain('left to pay');

    $component->assertSet('amount_due', 0);
});

test('a part payment that still fits after another payment goes through', function () {
    $invoice = payableInvoice();

    $component = Livewire::test(StaleInvoicePay::class, ['invoice' => $invoice])
        ->set('staleOutstanding', 22000)
        ->set('amount', '20.00');

    $invoice->update(['amount_paid' => 200, 'amount_due' => 20]);

    $component->call('pay');

    $invoice->refresh();

    expect((int) $invoice->getRawOriginal('amount_paid'))->toBe(22000)
        ->and($invoice->fully_paid_at)->not->toBeNull();
});

/*
 * After a part payment, opening the Pay drawer again showed an empty amount:
 * pay() cleared the field and only mount() filled it. And the show page's
 * header was never told, so the Paid badge waited for a reload.
 */

test('the drawer offers what is still due after a part payment, and a second payment cannot overpay', function () {
    $invoice = payableInvoice();

    $component = Livewire::test(InvoicePay::class, ['invoice' => $invoice])
        ->set('amount', '100.00')
        ->call('pay')
        ->assertHasNoErrors()
        ->assertSet('amount', 120)
        ->assertSet('amount_due', 120)
        ->assertSet('amount_paid', 100)
        ->assertDispatched('refreshInvoice');

    $component
        ->set('amount', '150.00')
        ->call('pay')
        ->assertHasErrors('amount');

    expect((int) $invoice->fresh()->getRawOriginal('amount_paid'))->toBe(10000);

    $component
        ->set('amount', '120.00')
        ->call('pay')
        ->assertHasNoErrors()
        ->assertSet('amount', null)
        ->assertSet('amount_due', 0)
        ->assertDispatched('refreshInvoice');

    expect($invoice->fresh()->fully_paid_at)->not->toBeNull()
        ->and((int) $invoice->fresh()->getRawOriginal('amount_paid'))->toBe(22000);
});

test('opening the drawer fills in the balance as it is now', function () {
    $invoice = payableInvoice();

    $component = Livewire::test(InvoicePay::class, ['invoice' => $invoice])
        ->assertSet('amount', 220);

    // Paid in part somewhere else while this page was open.
    $invoice->update(['amount_paid' => 200, 'amount_due' => 20]);

    $component
        ->set('amount', '')
        ->call('openPay')
        ->assertSet('showPayInvoice', true)
        ->assertSet('amount', 20)
        ->assertSet('amount_due', 20)
        ->assertSet('amount_paid', 200);
});

test('the Pay button opens the drawer through openPay', function () {
    Livewire::test(InvoicePay::class, ['invoice' => payableInvoice()])
        ->assertSee('wire:click="openPay"', false);
});
