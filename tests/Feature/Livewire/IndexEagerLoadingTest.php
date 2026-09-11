<?php

use Illuminate\Support\Facades\DB;
use VentureDrake\LaravelCrm\Livewire\Deals\DealIndex;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadIndex;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderIndex;
use VentureDrake\LaravelCrm\Livewire\Organizations\OrganizationIndex;
use VentureDrake\LaravelCrm\Livewire\People\PersonIndex;
use VentureDrake\LaravelCrm\Livewire\Products\ProductIndex;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteIndex;
use VentureDrake\LaravelCrm\Livewire\Tasks\TaskIndex;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\LeadSource;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\OrganizationType;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Models\XeroContact;
use VentureDrake\LaravelCrm\Models\XeroItem;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

/*
 * Every CRM index table is a MaryUI <x-mary-table>, which resolves a dotted
 * header key with data_get($row, 'ownerUser.name') — a lazy relation load per
 * row unless the list query loaded it. Add the relations each blade touches in
 * its @scope cells and a 25-row page was costing anywhere from 75 to nearly 500
 * queries.
 *
 * Each case below seeds one fully-populated row, runs the component's list
 * query, and then reads everything the page reads: every header key, plus the
 * relations and helpers the blade reaches for by hand. Any query at all during
 * that read-back is an N+1 that would multiply by 25 in production.
 */

beforeEach(function () {
    $this->actingAsUser(['crm_access' => 1]);
});

/**
 * A signed-in owner plus a label, the two things nearly every row carries.
 *
 * @return array{0: User, 1: Label}
 */
function indexRowFixtures(): array
{
    return [auth()->user(), Label::create(['name' => 'Hot', 'hex' => 'ff0000'])];
}

/**
 * A pipeline stage to hang a row off. The Pest-level pipeline seeding is scoped
 * to the authorization suite, so these cases make their own.
 */
function indexPipelineStage(): PipelineStage
{
    $pipeline = Pipeline::create(['name' => 'Index test pipeline', 'model' => Deal::class]);

    return $pipeline->pipelineStages()->create(['name' => 'Pending', 'order' => 0]);
}

/**
 * Money columns every document blade formats.
 *
 * @return array<string, mixed>
 */
function indexRowMoney(): array
{
    return ['subtotal' => 100, 'tax' => 10, 'total' => 110, 'discount' => 0, 'adjustments' => 0, 'currency' => 'USD'];
}

dataset('indexComponents', [
    'people' => [
        PersonIndex::class,
        'people',
        function () {
            [$owner, $label] = indexRowFixtures();

            $person = Person::create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'user_owner_id' => $owner->id]);
            $person->emails()->create(['address' => 'ada@example.com', 'primary' => 1]);
            $person->phones()->create(['number' => '555000', 'primary' => 1]);
            $person->labels()->attach($label);

            Deal::create(['title' => 'Open', 'person_id' => $person->id]);
            Deal::create(['title' => 'Won', 'person_id' => $person->id, 'closed_status' => 'won', 'closed_at' => now()]);
            Deal::create(['title' => 'Lost', 'person_id' => $person->id, 'closed_status' => 'lost', 'closed_at' => now()]);
        },
        function ($row) {
            $row->primaryEmail?->address;
            $row->primaryPhone?->number;
            $row->open_deals_count;
            $row->lost_deals_count;
            $row->won_deals_count;
            $row->labels->count();
        },
    ],

    'organizations' => [
        OrganizationIndex::class,
        'organizations',
        function () {
            [$owner, $label] = indexRowFixtures();

            $type = OrganizationType::create(['name' => 'Customer']);
            $organization = Organization::create([
                'name' => 'Analytical Engines',
                'user_owner_id' => $owner->id,
                'organization_type_id' => $type->id,
            ]);
            $organization->labels()->attach($label);

            XeroContact::create(['organization_id' => $organization->id, 'xero_id' => 'xero-1']);

            Deal::create(['title' => 'Open', 'organization_id' => $organization->id]);
            Deal::create(['title' => 'Won', 'organization_id' => $organization->id, 'closed_status' => 'won', 'closed_at' => now()]);
        },
        function ($row) {
            $row->xeroContact;
            $row->open_deals_count;
            $row->lost_deals_count;
            $row->won_deals_count;
            $row->labels->count();
        },
    ],

    'deals' => [
        DealIndex::class,
        'deals',
        function () {
            [$owner, $label] = indexRowFixtures();

            $deal = Deal::create([
                'title' => 'Engine order',
                'person_id' => Person::create(['first_name' => 'Ada'])->id,
                'organization_id' => Organization::create(['name' => 'Analytical Engines'])->id,
                'user_owner_id' => $owner->id,
                'pipeline_stage_id' => indexPipelineStage()->id,
            ]);
            $deal->labels()->attach($label);
        },
        function ($row) {
            $row->pipelineStage?->name;
            $row->labels->count();
        },
    ],

    'leads' => [
        LeadIndex::class,
        'leads',
        function () {
            [$owner, $label] = indexRowFixtures();

            $lead = Lead::create([
                'title' => 'Engine enquiry',
                'person_id' => Person::create(['first_name' => 'Ada'])->id,
                'organization_id' => Organization::create(['name' => 'Analytical Engines'])->id,
                'user_owner_id' => $owner->id,
                'pipeline_stage_id' => indexPipelineStage()->id,
                'lead_source_id' => LeadSource::create(['name' => 'Referral'])->id,
            ]);
            $lead->labels()->attach($label);
        },
        function ($row) {
            $row->pipelineStage?->name;
            $row->labels->count();
        },
    ],

    'quotes' => [
        QuoteIndex::class,
        'quotes',
        function () {
            [$owner, $label] = indexRowFixtures();

            $person = Person::create(['first_name' => 'Ada']);
            $person->emails()->create(['address' => 'ada@example.com', 'primary' => 1]);

            $quote = Quote::create(indexRowMoney() + [
                'title' => 'Engine quote',
                'person_id' => $person->id,
                'organization_id' => Organization::create(['name' => 'Analytical Engines'])->id,
                'user_owner_id' => $owner->id,
                'pipeline_stage_id' => indexPipelineStage()->id,
            ]);
            $quote->labels()->attach($label);

            $product = Product::create(['name' => 'Engine']);
            $quoteProduct = $quote->quoteProducts()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 100, 'amount' => 100]);

            $order = Order::create(indexRowMoney() + ['quote_id' => $quote->id]);
            $order->orderProducts()->create(['product_id' => $product->id, 'quote_product_id' => $quoteProduct->id, 'quantity' => 1, 'price' => 100, 'amount' => 100]);
        },
        function ($row) {
            $row->pipelineStage?->name;
            $row->labels->count();
            $row->person?->getPrimaryEmail();
            $row->orders->count();
            $row->orderComplete();
            VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\subTotal($row);
            VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\total($row);
        },
    ],

    'tasks' => [
        TaskIndex::class,
        'tasks',
        function () {
            [$owner] = indexRowFixtures();

            Task::create([
                'name' => 'Call Ada',
                'description' => 'About the engine',
                'user_owner_id' => $owner->id,
                'user_assigned_id' => $owner->id,
            ]);
        },
        fn ($row) => null,
    ],

    'products' => [
        ProductIndex::class,
        'products',
        function () {
            [$owner] = indexRowFixtures();

            $product = Product::create([
                'name' => 'Engine',
                'code' => 'ENG-1',
                'user_owner_id' => $owner->id,
                'product_category_id' => ProductCategory::create(['name' => 'Machines'])->id,
                'tax_rate_id' => TaxRate::create(['name' => 'GST', 'rate' => 10])->id,
            ]);

            $product->productPrices()->create(['currency' => 'USD', 'unit_price' => 100]);

            XeroItem::create(['product_id' => $product->id, 'xero_id' => 'xero-1']);
        },
        function ($row) {
            $row->xeroItem;
            $row->getDefaultPrice();
            $row->taxRate?->rate;
        },
    ],

    'orders' => [
        OrderIndex::class,
        'orders',
        function () {
            [$owner, $label] = indexRowFixtures();

            $product = Product::create(['name' => 'Engine']);

            $order = Order::create(indexRowMoney() + [
                'person_id' => Person::create(['first_name' => 'Ada'])->id,
                'organization_id' => Organization::create(['name' => 'Analytical Engines'])->id,
                'user_owner_id' => $owner->id,
                'quote_id' => Quote::create(indexRowMoney() + ['title' => 'Engine quote'])->id,
            ]);
            $order->labels()->attach($label);

            $orderProduct = $order->orderProducts()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 100, 'amount' => 100]);

            $invoice = Invoice::create(indexRowMoney() + ['order_id' => $order->id]);
            $invoice->invoiceLines()->create(['product_id' => $product->id, 'order_product_id' => $orderProduct->id, 'quantity' => 1, 'price' => 100, 'amount' => 100]);

            $delivery = Delivery::create(['order_id' => $order->id]);
            $delivery->deliveryProducts()->create(['product_id' => $product->id, 'order_product_id' => $orderProduct->id, 'quantity' => 1]);
        },
        function ($row) {
            $row->labels->count();
            $row->quote?->quote_id;
            $row->title;
            $row->invoiceComplete();
            $row->deliveryComplete();
            VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\subTotal($row);
            VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\tax($row);
            VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\total($row);
        },
    ],
]);

it('reads every column of an index row without a further query', function (
    string $component,
    string $method,
    Closure $seed,
    Closure $touch,
) {
    $seed();

    $index = app($component);

    if (method_exists($index, 'mount')) {
        $index->mount();
    }

    $rows = $index->{$method}();
    $headers = $index->headers();

    expect($rows)->not->toBeEmpty();

    DB::flushQueryLog();
    DB::enableQueryLog();

    foreach ($rows as $row) {
        // What MaryUI does for a header with no @scope override.
        foreach ($headers as $header) {
            data_get($row, $header['key']);
        }

        // What the blade's @scope cells and action column do by hand.
        $touch($row);
    }

    $queries = array_map(fn ($query) => $query['query'], DB::getQueryLog());

    DB::disableQueryLog();

    expect($queries)->toBe([]);
})->with('indexComponents');
