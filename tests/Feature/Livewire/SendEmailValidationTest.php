<?php

use Barryvdh\DomPDF\ServiceProvider as DomPdfServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Symfony\Component\Mailer\Exception\TransportException;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceSend;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderSend;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteSend;
use VentureDrake\LaravelCrm\Mail\SendInvoice;
use VentureDrake\LaravelCrm\Mail\SendPurchaseOrder;
use VentureDrake\LaravelCrm\Mail\SendQuote;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;

/*
 * The recipient field on the quote, invoice and purchase-order Send drawers.
 *
 * `to` was validated as `required|string`, so `not-an-email` got past
 * validation, the PDF was rendered and saved, and Symfony Mime then threw an
 * RfcComplianceException when the mailable set its To header - a 500. A
 * refused SMTP connection was a 500 as well. Now a bad address is a
 * validation error before any PDF work, a comma or semicolon list sends to
 * every address, and a transport failure is an error toast that leaves the
 * drawer open with what was typed.
 *
 * Mounted through render-stub subclasses because the drawers' blades reach
 * for tables the minimal TestSchema does not ship (as in PdfSendTest).
 */

class SendValidationQuoteSend extends QuoteSend
{
    public function render()
    {
        return '<div></div>';
    }
}

class SendValidationInvoiceSend extends InvoiceSend
{
    public function render()
    {
        return '<div></div>';
    }
}

class SendValidationPurchaseOrderSend extends PurchaseOrderSend
{
    public function render()
    {
        return '<div></div>';
    }
}

beforeEach(function () {
    $this->app->register(DomPdfServiceProvider::class);

    $this->actingAsUser(['crm_access' => 1]);
    Gate::before(fn () => true);

    // The subject templates read the organization_name setting unguarded.
    Setting::query()->delete();
    app('laravel-crm.settings')->set('organization_name', 'Acme Pty Ltd');
    app('laravel-crm.settings')->forgetCache();
});

dataset('send drawers', [
    'quote' => [
        SendValidationQuoteSend::class, 'quote', 'showSendQuote', SendQuote::class,
        fn () => Quote::create(['title' => 'Q', 'currency' => 'USD', 'total' => 110]),
    ],
    'invoice' => [
        SendValidationInvoiceSend::class, 'invoice', 'showSendInvoice', SendInvoice::class,
        fn () => Invoice::create(['currency' => 'USD', 'total' => 110]),
    ],
    'purchase order' => [
        SendValidationPurchaseOrderSend::class, 'purchaseOrder', 'showSendPurchaseOrder', SendPurchaseOrder::class,
        fn () => PurchaseOrder::create(['currency' => 'USD', 'total' => 110]),
    ],
]);

/**
 * Where send() writes the PDF for `$model`, mirroring the components.
 */
function sendValidationPdfDirectory($model): string
{
    return storage_path('app/laravel-crm/'.strtolower(class_basename($model)).'/'.$model->id);
}

it('rejects an invalid recipient before rendering the PDF', function (string $component, string $parameter, string $drawer, string $mailable, Closure $record) {
    Mail::fake();

    $model = $record();
    File::deleteDirectory(sendValidationPdfDirectory($model));

    $test = Livewire::test($component, [$parameter => $model])
        ->set('to', 'not-an-email')
        ->call('send')
        ->assertHasErrors('to');

    expect($test->errors()->first('to'))->toContain('not-an-email');

    Mail::assertNothingSent();
    expect(File::isDirectory(sendValidationPdfDirectory($model)))->toBeFalse();
})->with('send drawers');

it('rejects a list with one invalid recipient', function (string $component, string $parameter, string $drawer, string $mailable, Closure $record) {
    Mail::fake();

    $test = Livewire::test($component, [$parameter => $record()])
        ->set('to', 'buyer@example.com, oops')
        ->call('send')
        ->assertHasErrors('to');

    expect($test->errors()->first('to'))->toContain('oops');

    Mail::assertNothingSent();
})->with('send drawers');

it('sends to a single valid recipient', function (string $component, string $parameter, string $drawer, string $mailable, Closure $record) {
    Mail::fake();

    Livewire::test($component, [$parameter => $record()])
        ->set(['to' => 'buyer@example.com', 'subject' => 'S', 'message' => 'M'])
        ->call('send')
        ->assertHasNoErrors();

    Mail::assertSent($mailable, fn ($mail) => $mail->emailTo === ['buyer@example.com']);
})->with('send drawers');

it('sends one mail to every address in a comma or semicolon list', function (string $component, string $parameter, string $drawer, string $mailable, Closure $record) {
    Mail::fake();

    Livewire::test($component, [$parameter => $record()])
        ->set(['to' => 'a@example.com, b@example.com;', 'subject' => 'S', 'message' => 'M'])
        ->call('send')
        ->assertHasNoErrors();

    Mail::assertSentCount(1);
    Mail::assertSent($mailable, fn ($mail) => $mail->emailTo === ['a@example.com', 'b@example.com']);
})->with('send drawers');

it('keeps the drawer open with an error toast when the mail server refuses', function (string $component, string $parameter, string $drawer, string $mailable, Closure $record) {
    Mail::shouldReceive('send')->once()->andThrow(new TransportException('Connection refused'));

    $model = $record();

    $test = Livewire::test($component, [$parameter => $model])
        ->set($drawer, true)
        ->set(['to' => 'buyer@example.com', 'subject' => 'S', 'message' => 'M'])
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet($drawer, true)
        ->assertSet('to', 'buyer@example.com')
        ->assertNotDispatched('crm-document-sent');

    expect(json_encode($test->effects['xjs'] ?? []))
        ->toContain('The email could not be sent: Connection refused')
        ->not->toContain('sent successfully');

    // Quotes have no sent column.
    if (! $model instanceof Quote) {
        expect((bool) $model->fresh()->sent)->toBeFalse();
    }
})->with('send drawers');
