<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Http\Livewire\SendInvoice as LegacySendInvoice;
use VentureDrake\LaravelCrm\Http\Livewire\SendPurchaseOrder as LegacySendPurchaseOrder;
use VentureDrake\LaravelCrm\Http\Livewire\SendQuote as LegacySendQuote;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceSend;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderSend;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteSend;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;

/*
 * The default subject the Send dialogs pre-fill for quotes, invoices and
 * purchase orders. Every Send component — current and legacy — renders these
 * one-line views straight into the subject field, so the rendered string is
 * the subject verbatim.
 *
 * A record with no organization used to produce "... for" / "... for." with
 * nothing after it. The recipient now falls back to the linked person, and
 * the " for ..." clause is dropped when there is neither.
 *
 * The templates must not use Blade conditionals: while a Livewire component
 * is rendering, Livewire wraps every @if in <!--[if BLOCK]> morph-marker
 * comments. In the app each Send component is mounted inside its parent
 * page's render, so the markers land in the subject and message fields as
 * literal text. Neither rendering the view on its own nor Livewire::test()
 * on the Send component reproduces that, so the last tests below mount each
 * component inside a host component's render. The quote and invoice components
 * fill the fields when the drawer opens instead, so they are opened directly.
 */

// The components' blades reach for tables the minimal TestSchema does not
// ship, so each is mounted through a render-stub subclass (as in PdfSendTest)
// that records the fields the legacy and purchase order mount() pre-fills.
trait CapturesSendFields
{
    public static array $captured = [];

    public function render()
    {
        static::$captured = ['subject' => $this->subject, 'message' => $this->message];

        return '<div></div>';
    }
}

class SendSubjectQuoteSend extends QuoteSend
{
    use CapturesSendFields;
}

class SendSubjectLegacySendQuote extends LegacySendQuote
{
    use CapturesSendFields;
}

class SendSubjectInvoiceSend extends InvoiceSend
{
    use CapturesSendFields;
}

class SendSubjectLegacySendInvoice extends LegacySendInvoice
{
    use CapturesSendFields;
}

class SendSubjectPurchaseOrderSend extends PurchaseOrderSend
{
    use CapturesSendFields;
}

class SendSubjectLegacySendPurchaseOrder extends LegacySendPurchaseOrder
{
    use CapturesSendFields;
}

/**
 * Stands in for the show page that nests a Send component in its view.
 */
class SendSubjectHost extends Component
{
    public string $component;

    public string $parameter;

    public $model;

    public function render()
    {
        return '<div>@livewire($component, [$parameter => $model])</div>';
    }
}

beforeEach(function () {
    Setting::query()->delete();

    app('laravel-crm.settings')->set('organization_name', 'Venture Drake');
    app('laravel-crm.settings')->forgetCache();
});

/**
 * Renders `$view` for an unsaved `$model`, with its organization and person
 * relations set (or explicitly null) so no query runs.
 */
function renderSendSubject(string $view, string $variable, $model, ?string $organization, ?string $person): string
{
    $model->setRelation('organization', $organization ? new Organization(['name' => $organization]) : null);
    $model->setRelation('person', $person ? new Person(['first_name' => explode(' ', $person)[0], 'last_name' => explode(' ', $person)[1]]) : null);

    return view('laravel-crm::mail.templates.'.$view.'.subject', [$variable => $model])->render();
}

dataset('send subjects', [
    'quote' => [
        'send-quote', 'quote',
        fn () => new Quote(['quote_id' => 'QU-3002']),
        'Quote QU-3002 from Venture Drake',
        '.',
    ],
    'invoice' => [
        'send-invoice', 'invoice',
        fn () => new Invoice(['invoice_id' => 'INV-1001']),
        'Invoice INV-1001 from Venture Drake',
        '',
    ],
    'purchase order' => [
        'send-purchase-order', 'purchaseOrder',
        fn () => new PurchaseOrder(['purchase_order_id' => 'PO-1001']),
        'Purchase Order PO-1001 from Venture Drake',
        '',
    ],
]);

it('addresses the subject to the organization', function (string $view, string $variable, Closure $model, string $prefix, string $suffix) {
    expect(renderSendSubject($view, $variable, $model(), 'CedarPoint Financial', 'Jane Smith'))
        ->toBe($prefix.' for CedarPoint Financial'.$suffix);
})->with('send subjects');

it('falls back to the person when there is no organization', function (string $view, string $variable, Closure $model, string $prefix, string $suffix) {
    expect(renderSendSubject($view, $variable, $model(), null, 'Jane Smith'))
        ->toBe($prefix.' for Jane Smith'.$suffix);
})->with('send subjects');

it('drops the recipient clause when there is neither', function (string $view, string $variable, Closure $model, string $prefix, string $suffix) {
    expect(renderSendSubject($view, $variable, $model(), null, null))
        ->toBe($prefix.$suffix)
        ->not->toContain(' for');
})->with('send subjects');

/*
 * The quote email used to print `reference`, which is optional and blank on most
 * quotes, so the subject read "Quote  from ..." and the message "Here's quote  for ...".
 * The number is what the customer sees on the quote itself, reference or not.
 */
dataset('quote references', [
    'with a reference' => ['REF-3D00A599'],
    'without a reference' => [null],
]);

it('names the quote by its number in the subject', function (?string $reference) {
    $quote = new Quote(['quote_id' => 'QU-3002', 'reference' => $reference]);

    $subject = renderSendSubject('send-quote', 'quote', $quote, 'CedarPoint Financial', null);

    expect($subject)->toBe('Quote QU-3002 from Venture Drake for CedarPoint Financial.')
        ->not->toContain('  ');
})->with('quote references');

it('names the quote by its number in the message', function (?string $reference) {
    $quote = new Quote(['quote_id' => 'QU-3002', 'reference' => $reference, 'total' => 110, 'currency' => 'USD']);

    $message = view('laravel-crm::mail.templates.send-quote.message', ['quote' => $quote])->render();

    expect($message)
        ->toStartWith("Hi,\n\nHere's quote QU-3002 for ")
        ->toContain("Thanks,\nVenture Drake")
        ->not->toContain('REF-3D00A599')
        ->not->toContain('quote  for');
})->with('quote references');

it('falls back to the prefix and number when a quote has no stored quote id', function () {
    app('laravel-crm.settings')->set('quote_prefix', 'QU-');
    app('laravel-crm.settings')->forgetCache();

    $quote = new Quote;
    $quote->number = 3002;

    expect(renderSendSubject('send-quote', 'quote', $quote, 'CedarPoint Financial', null))
        ->toBe('Quote QU-3002 from Venture Drake for CedarPoint Financial.');
});

dataset('send components', [
    'legacy quote' => [SendSubjectLegacySendQuote::class, 'quote', fn ($person) => Quote::create(['title' => 'Q', 'currency' => 'USD', 'total' => 110, 'person_id' => $person->id])],
    'legacy invoice' => [SendSubjectLegacySendInvoice::class, 'invoice', fn ($person) => Invoice::create(['currency' => 'USD', 'total' => 110, 'person_id' => $person->id])],
    'purchase order' => [SendSubjectPurchaseOrderSend::class, 'purchaseOrder', fn ($person) => PurchaseOrder::create(['currency' => 'USD', 'total' => 110, 'person_id' => $person->id])],
    'legacy purchase order' => [SendSubjectLegacySendPurchaseOrder::class, 'purchaseOrder', fn ($person) => PurchaseOrder::create(['currency' => 'USD', 'total' => 110, 'person_id' => $person->id])],
]);

it('pre-fills a marker-free subject and message in the Send component', function (string $component, string $parameter, Closure $record) {
    $this->actingAsUser(['crm_access' => 1]);
    Gate::before(fn () => true);

    $model = $record(Person::create(['first_name' => 'Jane', 'last_name' => 'Smith']));

    Livewire::test(SendSubjectHost::class, ['component' => $component, 'parameter' => $parameter, 'model' => $model]);

    expect($component::$captured['subject'])
        ->toEndWith(str_contains($component, 'Quote') ? ' for Jane Smith.' : ' for Jane Smith')
        ->not->toContain('<!--');

    expect($component::$captured['message'])->not->toContain('<!--');
})->with('send components');

it('fills a marker-free subject and message when the Send drawer opens', function (string $component, string $parameter, Closure $record) {
    $this->actingAsUser(['crm_access' => 1]);
    Gate::before(fn () => true);

    $model = $record(Person::create(['first_name' => 'Jane', 'last_name' => 'Smith']));

    $test = Livewire::test($component, [$parameter => $model])->call('open');

    expect($test->get('subject'))
        ->toEndWith(str_contains($component, 'Quote') ? ' for Jane Smith.' : ' for Jane Smith')
        ->not->toContain('<!--');

    expect($test->get('message'))->not->toContain('<!--');
})->with([
    'quote' => [SendSubjectQuoteSend::class, 'quote', fn ($person) => Quote::create(['title' => 'Q', 'currency' => 'USD', 'total' => 110, 'person_id' => $person->id])],
    'invoice' => [SendSubjectInvoiceSend::class, 'invoice', fn ($person) => Invoice::create(['currency' => 'USD', 'total' => 110, 'person_id' => $person->id])],
]);
