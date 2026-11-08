<?php

/*
 * An end date can't fall on an earlier day than its start date (#106).
 *
 * An invoice could be saved due before it was issued, and then showed as
 * overdue straight away. Quotes (expiry vs issue) and purchase orders
 * (delivery vs issue) had the same gap: the forms had no date rules at all.
 *
 * Days are compared, not times, and a blank date on either side passes -
 * these dates are optional.
 *
 * The forms are mounted through render-stub subclasses, as in
 * ContactValidationTest, so the real rules run without the blades.
 */

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Http\Requests\StoreInvoiceRequest;
use VentureDrake\LaravelCrm\Http\Requests\StorePurchaseOrderRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateInvoiceRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdatePurchaseOrderRequest;
use VentureDrake\LaravelCrm\Http\Rules\NotBeforeDate;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceCreate;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceEdit;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderCreate;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderEdit;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteCreate;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteEdit;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;

class DateOrderInvoiceCreate extends InvoiceCreate
{
    public function render()
    {
        return '<div></div>';
    }
}

class DateOrderInvoiceEdit extends InvoiceEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

class DateOrderQuoteCreate extends QuoteCreate
{
    public function render()
    {
        return '<div></div>';
    }
}

class DateOrderQuoteEdit extends QuoteEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

class DateOrderPurchaseOrderCreate extends PurchaseOrderCreate
{
    public function render()
    {
        return '<div></div>';
    }
}

class DateOrderPurchaseOrderEdit extends PurchaseOrderEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

beforeEach(function () {
    $this->actingAsUser(['crm_access' => 1]);
    Gate::before(fn () => true);

    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);
    app('laravel-crm.settings')->forgetCache();

    foreach ([Invoice::class, Quote::class, PurchaseOrder::class] as $model) {
        Pipeline::create([
            'external_id' => Str::uuid()->toString(),
            'name' => class_basename($model).' Pipeline',
            'model' => $model,
        ])->pipelineStages()->create([
            'external_id' => Str::uuid()->toString(),
            'name' => 'Draft',
            'order' => 0,
        ]);
    }
});

/*
 * [component, mount params, start field, end field, extra fields to set,
 *  the model class, the record being edited (null on create)]
 */
dataset('document forms', [
    'invoice create' => fn () => [DateOrderInvoiceCreate::class, [], 'issue_date', 'due_date', [], Invoice::class, null],
    'invoice edit' => function () {
        $invoice = Invoice::create(['currency' => 'USD', 'reference' => 'Original']);

        return [DateOrderInvoiceEdit::class, ['invoice' => $invoice], 'issue_date', 'due_date', [], Invoice::class, $invoice];
    },
    'quote create' => fn () => [DateOrderQuoteCreate::class, [], 'issue_at', 'expire_at', ['title' => 'A quote'], Quote::class, null],
    'quote edit' => function () {
        $quote = Quote::create(['title' => 'A quote', 'currency' => 'USD', 'reference' => 'Original']);

        return [DateOrderQuoteEdit::class, ['quote' => $quote], 'issue_at', 'expire_at', [], Quote::class, $quote];
    },
    'purchase order create' => fn () => [DateOrderPurchaseOrderCreate::class, [], 'issue_date', 'delivery_date', ['delivery_type' => 'pickup'], PurchaseOrder::class, null],
    'purchase order edit' => function () {
        $purchaseOrder = PurchaseOrder::create(['currency' => 'USD', 'delivery_type' => 'pickup', 'reference' => 'Original']);

        return [DateOrderPurchaseOrderEdit::class, ['purchaseOrder' => $purchaseOrder], 'issue_date', 'delivery_date', ['delivery_type' => 'pickup'], PurchaseOrder::class, $purchaseOrder];
    },
]);

function dateOrderForm(array $form, ?string $start, ?string $end)
{
    [$component, $params, $startField, $endField, $extra] = $form;

    $test = Livewire::test($component, $params)
        ->set('organization_name', 'Acme')
        ->set('reference', 'Changed');

    foreach ($extra as $field => $value) {
        $test->set($field, $value);
    }

    return $test->set($startField, $start)->set($endField, $end)->call('save');
}

it('rejects an end date before the start date and saves nothing', function (array $form) {
    [, , , $endField, , $model, $record] = $form;

    // The case reported in #106.
    dateOrderForm($form, '2026-11-08', '2026-11-01')
        ->assertHasErrors([$endField]);

    if ($record) {
        expect($record->fresh()->reference)->toBe('Original');
    } else {
        expect($model::count())->toBe(0);
    }
})->with('document forms');

it('names the start date in the message', function (array $form) {
    [, , , $endField] = $form;

    $errors = dateOrderForm($form, '2026-11-08', '2026-11-01')->errors();

    expect($errors->first($endField))->toContain("can't be before the issue date");
})->with('document forms');

it('accepts an end date on the same day as the start date', function (array $form) {
    [, , $startField, $endField, , $model, $record] = $form;

    dateOrderForm($form, '2026-11-08', '2026-11-08')
        ->assertHasNoErrors([$startField, $endField]);

    if ($record) {
        expect($record->fresh()->reference)->toBe('Changed');
    } else {
        expect($model::count())->toBe(1);
    }
})->with('document forms');

it('accepts an end date after the start date', function (array $form) {
    [, , $startField, $endField] = $form;

    dateOrderForm($form, '2026-11-01', '2026-11-08')
        ->assertHasNoErrors([$startField, $endField]);
})->with('document forms');

it('leaves either date optional', function (array $form, ?string $start, ?string $end) {
    [, , $startField, $endField] = $form;

    dateOrderForm($form, $start, $end)
        ->assertHasNoErrors([$startField, $endField]);
})->with('document forms')->with([
    'no start' => [null, '2026-11-01'],
    'no end' => ['2026-11-08', null],
    'neither' => [null, null],
    'blank strings' => ['', ''],
]);

it('rejects a date that does not parse', function (array $form) {
    [, , $startField, $endField] = $form;

    dateOrderForm($form, 'not a date', 'nor this')
        ->assertHasErrors([$startField, $endField]);
})->with('document forms');

describe('the rule', function () {
    it('compares days, not times', function (string $end, string $start, ?string $timezone, bool $before) {
        expect(NotBeforeDate::isBefore($end, $start, $timezone))->toBe($before);
    })->with([
        'earlier day' => ['2026-11-01', '2026-11-08', null, true],
        'same day, earlier time' => ['2026-11-08T00:00:00Z', '2026-11-08T14:23:00Z', null, false],
        'later day' => ['2026-11-09', '2026-11-08', null, false],
        'converted onto an earlier day' => ['2026-11-08T02:00:00+05:00', '2026-11-08T00:00:00Z', 'UTC', true],
        'unconverted, same day' => ['2026-11-08T02:00:00+05:00', '2026-11-08T00:00:00Z', null, false],
    ]);

    it('passes a blank or unparseable date on either side', function (mixed $end, mixed $start) {
        expect(NotBeforeDate::isBefore($end, $start))->toBeFalse();
    })->with([
        [null, '2026-11-08'],
        ['2026-11-01', null],
        ['', '2026-11-08'],
        ['garbage', '2026-11-08'],
        ['2026-11-01', 'garbage'],
    ]);
});

it('applies the same check to the web requests behind the older forms', function (string $request, string $endField) {
    Setting::updateOrCreate(['name' => 'date_format'], ['value' => 'Y/m/d']);

    $rules = array_intersect_key((new $request)->rules(), array_flip(['issue_date', $endField]));

    expect($rules)->toHaveCount(2)
        ->and(Validator::make(['issue_date' => '2026/11/08', $endField => '2026/11/01'], $rules)->errors()->has($endField))->toBeTrue()
        ->and(Validator::make(['issue_date' => '2026/11/08', $endField => '2026/11/08'], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['issue_date' => '2026/11/08', $endField => '2026/11/09'], $rules)->passes())->toBeTrue();
})->with([
    'store invoice' => [StoreInvoiceRequest::class, 'due_date'],
    'update invoice' => [UpdateInvoiceRequest::class, 'due_date'],
    'store purchase order' => [StorePurchaseOrderRequest::class, 'delivery_date'],
    'update purchase order' => [UpdatePurchaseOrderRequest::class, 'delivery_date'],
]);

it('leaves a purchase order delivery date optional on the older forms', function (string $request) {
    Setting::updateOrCreate(['name' => 'date_format'], ['value' => 'Y/m/d']);

    $rules = array_intersect_key((new $request)->rules(), array_flip(['issue_date', 'delivery_date']));

    expect(Validator::make(['issue_date' => '2026/11/08', 'delivery_date' => null], $rules)->passes())->toBeTrue();
})->with([
    'store' => [StorePurchaseOrderRequest::class],
    'update' => [UpdatePurchaseOrderRequest::class],
]);
