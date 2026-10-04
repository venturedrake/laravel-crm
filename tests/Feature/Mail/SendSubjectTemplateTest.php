<?php

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
 */

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
        fn () => new Quote(['reference' => 'REF-3D00A599']),
        'Quote REF-3D00A599 from Venture Drake',
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
