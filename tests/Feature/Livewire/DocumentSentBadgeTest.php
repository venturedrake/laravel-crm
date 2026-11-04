<?php

use Barryvdh\DomPDF\ServiceProvider as DomPdfServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceIndex;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceRelatedIndex;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceSend;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceShow;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderIndex;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderRelatedIndex;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderSend;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderShow;
use VentureDrake\LaravelCrm\Mail\SendInvoice;
use VentureDrake\LaravelCrm\Mail\SendPurchaseOrder;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Setting;

/*
 * The green "Sent" badge on invoices and purchase orders.
 *
 * The invoice index, the invoice show page and the purchase-order index all
 * tested `sent != 1`, so every new document showed as Sent before anyone had
 * sent it. Flipping the test alone would have left the badge never showing:
 * the Send drawers mailed the document without ever setting `sent`.
 *
 * The index and show tests render the real blades (as InvoiceNullDatesTest
 * does). The Send components are mounted through render-stub subclasses
 * because their blades reach for tables the minimal TestSchema does not ship
 * (as in PdfSendTest); `send()` itself, including the DomPDF render, runs for
 * real.
 */

class SentBadgeInvoiceSend extends InvoiceSend
{
    public function render()
    {
        return '<div></div>';
    }
}

class SentBadgePurchaseOrderSend extends PurchaseOrderSend
{
    public function render()
    {
        return '<div></div>';
    }
}

// The badge markup. The column header also says "Sent", so asserting on the
// word would match an empty table; nothing else on either index renders a
// badge-success.
const SENT_BADGE = 'badge-success';

beforeEach(function () {
    $this->app->register(DomPdfServiceProvider::class);

    // The index row actions mount the Send components, whose subject blades
    // read the organization_name setting unguarded.
    Setting::query()->delete();
    app('laravel-crm.settings')->set('organization_name', 'Test Organization');
    app('laravel-crm.settings')->forgetCache();

    $this->actingAsUserWithPermissions([
        'view crm invoices',
        'create crm invoices',
        'edit crm invoices',
        'delete crm invoices',
        'view crm purchase orders',
        'create crm purchase orders',
        'edit crm purchase orders',
        'delete crm purchase orders',
    ]);
});

it('shows no Sent badge on a new invoice in the index', function () {
    Invoice::create(['reference' => 'Unsent invoice', 'currency' => 'USD']);

    Livewire::test(InvoiceIndex::class)
        ->assertOk()
        ->assertSee('Unsent invoice')
        ->assertDontSeeHtml(SENT_BADGE);
});

it('shows the Sent badge on a sent invoice in the index', function () {
    Invoice::create(['reference' => 'Sent invoice', 'currency' => 'USD', 'sent' => 1]);

    Livewire::test(InvoiceIndex::class)
        ->assertOk()
        ->assertSeeHtml(SENT_BADGE);
});

it('shows no Sent badge on a new purchase order in the index', function () {
    PurchaseOrder::create(['reference' => 'Unsent purchase order', 'currency' => 'USD']);

    Livewire::test(PurchaseOrderIndex::class)
        ->assertOk()
        ->assertSee('Unsent purchase order')
        ->assertDontSeeHtml(SENT_BADGE);
});

it('shows the Sent badge on a sent purchase order in the index', function () {
    PurchaseOrder::create(['reference' => 'Sent purchase order', 'currency' => 'USD', 'sent' => 1]);

    Livewire::test(PurchaseOrderIndex::class)
        ->assertOk()
        ->assertSeeHtml(SENT_BADGE);
});

it('shows the Sent badge on the invoice show page only once sent', function () {
    $unsent = Invoice::create(['reference' => 'Unsent invoice', 'currency' => 'USD']);
    $sent = Invoice::create(['reference' => 'Sent invoice', 'currency' => 'USD', 'sent' => 1]);

    Livewire::test(InvoiceShow::class, ['invoice' => $unsent])
        ->assertOk()
        ->assertDontSeeHtml(SENT_BADGE);

    Livewire::test(InvoiceShow::class, ['invoice' => $sent])
        ->assertOk()
        ->assertSeeHtml(SENT_BADGE);
});

it('shows the Sent badge on the purchase order show page only once sent', function () {
    $unsent = PurchaseOrder::create(['reference' => 'Unsent purchase order', 'currency' => 'USD']);
    $sent = PurchaseOrder::create(['reference' => 'Sent purchase order', 'currency' => 'USD', 'sent' => 1]);

    Livewire::test(PurchaseOrderShow::class, ['purchaseOrder' => $unsent])
        ->assertOk()
        ->assertDontSeeHtml(SENT_BADGE);

    Livewire::test(PurchaseOrderShow::class, ['purchaseOrder' => $sent])
        ->assertOk()
        ->assertSeeHtml(SENT_BADGE);
});

it('tests the sent flag the right way round in every blade', function () {
    // Covers the related-index and portal blades the tests above do not
    // render: every view that reads `->sent` must not show it when unsent.
    $views = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__.'/../../../resources/views')
    );

    $checked = 0;

    foreach ($views as $view) {
        if (! str_ends_with($view->getFilename(), '.blade.php')) {
            continue;
        }

        $blade = file_get_contents($view->getPathname());

        if (! str_contains($blade, '->sent')) {
            continue;
        }

        $checked++;

        expect($blade)->not->toMatch('/->sent\s*!=/', $view->getPathname());
    }

    // The invoice and purchase-order index, show and related-index blades,
    // plus the purchase-order portal page.
    expect($checked)->toBeGreaterThanOrEqual(7);
});

it('marks an invoice as sent when the Send drawer mails it', function () {
    Mail::fake();
    Gate::before(fn () => true);

    $invoice = Invoice::create(['reference' => 'To send', 'currency' => 'USD', 'total' => 110]);

    expect((bool) $invoice->sent)->toBeFalse();

    Livewire::test(SentBadgeInvoiceSend::class, ['invoice' => $invoice])
        ->call('open')
        ->set('to', 'buyer@example.com')
        ->call('send')
        ->assertHasNoErrors()
        ->assertDispatched('crm-document-sent');

    Mail::assertSent(SendInvoice::class);
    expect((bool) $invoice->fresh()->sent)->toBeTrue();

    // What the user sees once the dispatched event has refreshed the table.
    Livewire::test(InvoiceIndex::class)->assertSeeHtml(SENT_BADGE);
});

it('marks a purchase order as sent when the Send drawer mails it', function () {
    Mail::fake();
    Gate::before(fn () => true);

    $purchaseOrder = PurchaseOrder::create(['reference' => 'To send', 'currency' => 'USD', 'total' => 110]);

    expect((bool) $purchaseOrder->sent)->toBeFalse();

    Livewire::test(SentBadgePurchaseOrderSend::class, ['purchaseOrder' => $purchaseOrder])
        ->set('to', 'supplier@example.com')
        ->call('send')
        ->assertHasNoErrors()
        ->assertDispatched('crm-document-sent');

    Mail::assertSent(SendPurchaseOrder::class);
    expect((bool) $purchaseOrder->fresh()->sent)->toBeTrue();

    Livewire::test(PurchaseOrderIndex::class)->assertSeeHtml(SENT_BADGE);
});

it('refreshes every page that shows the badge when a document is sent', function (string $component) {
    $listeners = (fn () => $this->getListeners())->call(new $component);

    expect($listeners)->toHaveKey('crm-document-sent', '$refresh');
})->with([
    InvoiceIndex::class,
    InvoiceShow::class,
    InvoiceRelatedIndex::class,
    PurchaseOrderIndex::class,
    PurchaseOrderShow::class,
    PurchaseOrderRelatedIndex::class,
]);
