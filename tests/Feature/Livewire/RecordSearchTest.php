<?php

/*
 * Searching a list or board by a record's number, and its reference.
 *
 * "QU-3002" on the quotes list found nothing: the search only looked at the title
 * and the contact, and most lists were the same. Deliveries had a search box with
 * no search behind it. Terms also went straight into LIKE, so "%" matched every
 * row. All of it now goes through RecordSearch.
 *
 * The real blades render here, because the search query is what is under test.
 * Only quotes, orders, invoices and purchase orders have a reference column: a delivery
 * is found by its order's reference in both the test and host schemas.
 */

use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Calls\CallRelated;
use VentureDrake\LaravelCrm\Livewire\Deals\DealBoard;
use VentureDrake\LaravelCrm\Livewire\Deals\DealIndex;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryIndex;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceIndex;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadBoard;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadIndex;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderIndex;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderIndex;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteBoard;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteIndex;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Support\RecordSearch;

beforeEach(function () {
    foreach ([
        'quote_prefix' => 'QU-', 'order_prefix' => 'ORD-', 'invoice_prefix' => 'INV-',
        'purchase_order_prefix' => 'PO-', 'delivery_prefix' => 'DEL-', 'lead_prefix' => 'LD-',
        'deal_prefix' => 'DL-', 'currency' => 'USD', 'team' => 'related',
    ] as $name => $value) {
        Setting::updateOrCreate(['name' => $name], ['value' => $value]);
    }

    app('laravel-crm.settings')->forgetCache();

    foreach ([Lead::class, Deal::class, Quote::class] as $model) {
        $pipeline = Pipeline::create([
            'external_id' => Str::uuid()->toString(),
            'name' => class_basename($model).' Pipeline',
            'model' => $model,
        ]);
        $pipeline->pipelineStages()->create([
            'external_id' => Str::uuid()->toString(),
            'name' => 'First',
            'order' => 0,
        ]);
    }

    $this->actingAsUserWithPermissions([
        'view crm quotes', 'view crm orders', 'view crm invoices', 'view crm purchase orders',
        'view crm deliveries', 'view crm leads', 'view crm deals',
    ]);
});

/**
 * @return array<int> the ids a list shows for a term
 */
function listSearch(string $component, string $variable, string $term): array
{
    return Livewire::test($component)
        ->set('search', $term)
        ->assertOk()
        ->viewData($variable)
        ->pluck('id')
        ->all();
}

/**
 * @return array<int> the ids a board's first column shows for a term
 */
function boardSearch(string $component, string $model, string $term): array
{
    $stage = Pipeline::where('model', $model)->first()->pipelineStages()->first();

    return collect(Livewire::test($component)->set('search', $term)->assertOk()->viewData('stages'))
        ->firstWhere('id', $stage->id)['records']
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->all();
}

dataset('lists', [
    'quotes' => [QuoteIndex::class, 'quotes', Quote::class, 'quote_id', ['title' => 'A quote']],
    'orders' => [OrderIndex::class, 'orders', Order::class, 'order_id', []],
    'invoices' => [InvoiceIndex::class, 'invoices', Invoice::class, 'invoice_id', []],
    'purchase orders' => [PurchaseOrderIndex::class, 'purchaseOrders', PurchaseOrder::class, 'purchase_order_id', []],
    'deliveries' => [DeliveryIndex::class, 'deliveries', Delivery::class, 'delivery_id', []],
    'leads' => [LeadIndex::class, 'leads', Lead::class, 'lead_id', ['title' => 'A lead']],
    'deals' => [DealIndex::class, 'deals', Deal::class, 'deal_id', ['title' => 'A deal']],
]);

dataset('referenced lists', [
    'quotes' => [QuoteIndex::class, 'quotes', Quote::class, ['title' => 'A quote']],
    'orders' => [OrderIndex::class, 'orders', Order::class, []],
    'invoices' => [InvoiceIndex::class, 'invoices', Invoice::class, []],
    'purchase orders' => [PurchaseOrderIndex::class, 'purchaseOrders', PurchaseOrder::class, []],
]);

dataset('boards', [
    'quotes' => [QuoteBoard::class, Quote::class, 'quote_id'],
    'leads' => [LeadBoard::class, Lead::class, 'lead_id'],
    'deals' => [DealBoard::class, Deal::class, 'deal_id'],
]);

dataset('encryption', ['off' => [false], 'on' => [true]]);

it('finds a record by its full, bare and lowercase number', function (string $component, string $variable, string $model, string $column, array $attributes, bool $encrypted) {
    config()->set('laravel-crm.encrypt_db_fields', $encrypted);

    $model::create($attributes);
    $target = $model::create($attributes);
    $model::create($attributes);

    $number = $target->fresh()->{$column};
    $bare = (string) $target->fresh()->number;

    expect($number)->toEndWith($bare)
        ->and(listSearch($component, $variable, $number))->toBe([$target->id])
        ->and(listSearch($component, $variable, $bare))->toBe([$target->id])
        ->and(listSearch($component, $variable, Str::lower($number)))->toBe([$target->id])
        ->and(listSearch($component, $variable, 'ZZ-'.$bare))->toBeEmpty();
})->with('lists')->with('encryption');

it('finds a card by its full, bare and lowercase number', function (string $component, string $model, string $column, bool $encrypted) {
    config()->set('laravel-crm.encrypt_db_fields', $encrypted);

    $stage = Pipeline::where('model', $model)->first()->pipelineStages()->first();
    $attributes = ['title' => 'A record', 'pipeline_stage_id' => $stage->id];

    $model::create($attributes);
    $target = $model::create($attributes);

    $number = $target->fresh()->{$column};

    expect(boardSearch($component, $model, $number))->toBe([$target->id])
        ->and(boardSearch($component, $model, (string) $target->fresh()->number))->toBe([$target->id])
        ->and(boardSearch($component, $model, Str::lower($number)))->toBe([$target->id]);
})->with('boards')->with('encryption');

it('keeps finding a number after the prefix setting changes', function () {
    $quote = Quote::create(['title' => 'A quote']);
    $number = $quote->fresh()->quote_id;

    Setting::updateOrCreate(['name' => 'quote_prefix'], ['value' => 'Q2-']);
    app('laravel-crm.settings')->forgetCache();

    expect(listSearch(QuoteIndex::class, 'quotes', $number))->toBe([$quote->id])
        ->and(listSearch(QuoteIndex::class, 'quotes', (string) $quote->fresh()->number))->toBe([$quote->id]);
});

it('finds a record stored without a display number from its parts', function () {
    $quote = Quote::create(['title' => 'A quote']);
    Quote::whereKey($quote->id)->update(['quote_id' => null, 'prefix' => 'OLD-', 'number' => 42]);

    expect(listSearch(QuoteIndex::class, 'quotes', 'OLD-42'))->toBe([$quote->id])
        ->and(listSearch(QuoteIndex::class, 'quotes', 'old-42'))->toBe([$quote->id])
        ->and(listSearch(QuoteIndex::class, 'quotes', '42'))->toBe([$quote->id])
        ->and(listSearch(QuoteIndex::class, 'quotes', 'QU-42'))->toBeEmpty();
});

it('finds a record with no stored prefix under the current one', function () {
    $quote = Quote::create(['title' => 'A quote']);
    Quote::whereKey($quote->id)->update(['quote_id' => null, 'prefix' => null, 'number' => 42]);

    expect(listSearch(QuoteIndex::class, 'quotes', 'qu-42'))->toBe([$quote->id])
        ->and($quote->fresh()->quote_id)->toBe('QU-42');
});

it('finds a record with no stored number by the id it displays', function () {
    $quote = Quote::create(['title' => 'A quote']);
    Quote::whereKey($quote->id)->update(['quote_id' => null, 'number' => null]);

    expect(listSearch(QuoteIndex::class, 'quotes', 'QU-'.$quote->id))->toBe([$quote->id]);
});

it('finds a record by its reference', function (string $component, string $variable, string $model, array $attributes, bool $encrypted) {
    config()->set('laravel-crm.encrypt_db_fields', $encrypted);

    $target = $model::create([...$attributes, 'reference' => 'Needle-77']);
    $model::create([...$attributes, 'reference' => 'Other']);

    expect(listSearch($component, $variable, 'needle-77'))->toBe([$target->id]);
})->with('referenced lists')->with('encryption');

it('finds a delivery by its order\'s reference', function () {
    $order = Order::create(['reference' => 'Needle-77', 'currency' => 'USD']);
    $target = Delivery::create(['order_id' => $order->id]);
    Delivery::create(['order_id' => Order::create(['reference' => 'Other', 'currency' => 'USD'])->id]);

    expect(listSearch(DeliveryIndex::class, 'deliveries', 'needle'))->toBe([$target->id]);
});

it('finds a card on the quote board by its reference', function () {
    $stage = Pipeline::where('model', Quote::class)->first()->pipelineStages()->first();

    $target = Quote::create(['title' => 'A quote', 'reference' => 'Needle-77', 'pipeline_stage_id' => $stage->id]);
    Quote::create(['title' => 'A quote', 'reference' => 'Other', 'pipeline_stage_id' => $stage->id]);

    expect(boardSearch(QuoteBoard::class, Quote::class, 'needle-77'))->toBe([$target->id]);
});

it('still finds a record by its contact', function (string $component, string $variable, string $model, array $attributes, bool $encrypted) {
    config()->set('laravel-crm.encrypt_db_fields', $encrypted);

    $person = Person::create(['first_name' => 'Rosalind', 'last_name' => 'Franklin']);
    $target = $model::create([...$attributes, 'person_id' => $person->id]);
    $model::create($attributes);

    expect(listSearch($component, $variable, 'Rosalind Franklin'))->toBe([$target->id]);
})->with('referenced lists')->with('encryption');

it('treats wildcards in a term as text', function (string $component, string $variable, string $model, string $column, array $attributes, string $term) {
    $model::create($attributes);
    $model::create($attributes);

    expect(listSearch($component, $variable, $term))->toBeEmpty();
})->with('lists')->with(['%', '_', '%%', 'QU%', '\\%', '!']);

it('treats wildcards in a term as text on a board', function (string $component, string $model, string $column, string $term) {
    $stage = Pipeline::where('model', $model)->first()->pipelineStages()->first();

    $model::create(['title' => 'A record', 'pipeline_stage_id' => $stage->id]);

    expect(boardSearch($component, $model, $term))->toBeEmpty();
})->with('boards')->with(['%', '_']);

it('still finds a literal percent sign', function () {
    $target = Quote::create(['title' => 'A 50% deposit']);
    Quote::create(['title' => 'A 500 deposit']);

    expect(listSearch(QuoteIndex::class, 'quotes', '50%'))->toBe([$target->id]);
});

it('compares a number only when the column can hold it', function (string $term, int $bindings) {
    $query = Lead::query()->where(fn ($query) => RecordSearch::orNumber($query, $term));

    expect($query->getBindings())->toHaveCount($bindings);
})->with([
    'the largest it can hold' => ['2147483647', 4],
    'one too many' => ['2147483648', 2],
    'a phone number' => ['61412345678', 2],
    'a prefixed number' => ['LD-61412345678', 2],
    'more digits than a bigint' => ['12345678901234567890123', 2],
]);

it('does not spend longer on a very long term', function (string $term) {
    $start = microtime(true);

    RecordSearch::orNumber(Lead::query(), $term);

    expect(microtime(true) - $start)->toBeLessThan(1.0);
})->with([
    'digits then a letter' => [str_repeat('1', 300_000).'a'],
    'only digits' => [str_repeat('1', 300_000)],
]);

it('still finds a number too big to compare by its stored form', function () {
    $target = Invoice::create([]);
    $target->forceFill(['number' => 3000000000, 'invoice_id' => 'INV-3000000000'])->saveQuietly();
    Invoice::create([]);

    expect(listSearch(InvoiceIndex::class, 'invoices', '3000000000'))->toBe([$target->id]);
});

it('finds text outside ASCII as typed, on any database', function () {
    $target = Quote::create(['title' => 'Élodie Durand']);
    Quote::create(['title' => 'Someone else']);

    expect(listSearch(QuoteIndex::class, 'quotes', 'Élodie'))->toBe([$target->id]);
});

it('treats a wildcard as text in the guest picker', function () {
    $this->actingAsUserWithPermissions(['create crm calls']);
    Person::create(['first_name' => 'Rosalind']);

    // An organisation, because a call on a person page starts with that
    // person as a guest and so always has one option.
    Livewire::test(CallRelated::class, ['model' => Organization::create(['name' => 'Parent'])])
        ->call('searchGuests', '%')
        ->assertSet('guestOptions', []);
});
