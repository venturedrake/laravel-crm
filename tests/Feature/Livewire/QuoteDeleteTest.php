<?php

/*
 * A quote with an order cannot be deleted.
 *
 * QU-3001 was ordered as ORD-2070 and then deleted: every delete path only
 * checked the `delete crm quotes` permission, and ORD-2070 lost its quote
 * link because Order::quote() skipped soft-deleted quotes. The rule now lives
 * on the quote (canBeDeleted / deleteBlockedReason) and in the delete policy,
 * so the show page, index, board, web route and API are all held to it. An
 * order whose quote was deleted before the fix shows the quote number with a
 * "deleted" badge instead of a link that would 404.
 */

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderIndex;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderShow;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteBoard;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteIndex;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteShow;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

class DeleteQuoteShow extends QuoteShow
{
    public function render()
    {
        return '<div></div>';
    }
}

class DeleteQuoteIndex extends QuoteIndex
{
    public function render()
    {
        return '<div></div>';
    }
}

class DeleteQuoteBoard extends QuoteBoard
{
    public function render()
    {
        return '<div></div>';
    }
}

beforeEach(function () {
    $this->actingAsUserWithPermissions(['view crm quotes', 'edit crm quotes', 'delete crm quotes', 'view crm orders']);

    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);
    app('laravel-crm.settings')->forgetCache();

    $pipeline = Pipeline::create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'Quote Pipeline',
        'model' => Quote::class,
    ]);

    foreach (['Draft', 'Accepted', 'Rejected'] as $order => $stage) {
        $pipeline->pipelineStages()->create([
            'external_id' => Str::uuid()->toString(),
            'name' => $stage,
            'order' => $order,
        ]);
    }
});

function deleteTestQuote(array $attributes = []): Quote
{
    return Quote::create(array_merge(['title' => 'Fit-out', 'currency' => 'USD'], $attributes))->fresh();
}

function deleteTestOrderedQuote(): Quote
{
    $quote = deleteTestQuote(['accepted_at' => now()]);
    Order::create(['quote_id' => $quote->id]);

    return $quote->fresh();
}

function deleteTestReason(): string
{
    return ucfirst(__('laravel-crm::lang.quote_delete_ordered'));
}

function deleteTestToast($component): string
{
    // Mary shows a toast through a js effect.
    return json_encode($component->effects['xjs'] ?? [], JSON_UNESCAPED_UNICODE);
}

test('a quote with an order cannot be deleted, and says why', function () {
    $quote = deleteTestOrderedQuote();

    expect($quote->canBeDeleted())->toBeFalse()
        ->and($quote->deleteBlockedReason())->toBe(deleteTestReason());

    $response = Gate::forUser(auth()->user())->inspect('delete', $quote);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe(deleteTestReason());
});

test('a quote with no order can be deleted whatever its state', function (array $attributes) {
    $quote = deleteTestQuote($attributes);

    expect($quote->canBeDeleted())->toBeTrue()
        ->and($quote->deleteBlockedReason())->toBeNull()
        ->and(auth()->user()->can('delete', $quote))->toBeTrue();
})->with([
    'draft' => [[]],
    'sent' => [['sent' => 1]],
    'accepted' => [['accepted_at' => now()]],
    'rejected' => [['rejected_at' => now()]],
]);

test('a soft-deleted order does not stop the quote being deleted', function () {
    $quote = deleteTestQuote(['accepted_at' => now()]);
    Order::create(['quote_id' => $quote->id])->delete();

    expect($quote->fresh()->canBeDeleted())->toBeTrue()
        ->and(auth()->user()->can('delete', $quote->fresh()))->toBeTrue();
});

test('the delete permission is still needed for a quote with no order', function () {
    $this->actingAsUserWithPermissions(['view crm quotes']);

    expect(auth()->user()->can('delete', deleteTestQuote()))->toBeFalse();
});

test('the show page, index and board refuse to delete an ordered quote with a message', function (string $component) {
    $quote = deleteTestOrderedQuote();

    $params = $component === DeleteQuoteShow::class ? ['quote' => $quote] : [];

    $test = Livewire::test($component, $params)->call('delete', $quote->id)->assertOk();

    expect(Quote::find($quote->id))->not->toBeNull()
        ->and(deleteTestToast($test))->toContain(deleteTestReason());
})->with([
    'show' => DeleteQuoteShow::class,
    'index' => DeleteQuoteIndex::class,
    'board' => DeleteQuoteBoard::class,
]);

test('the show page, index and board still delete a quote with no order', function (string $component) {
    $quote = deleteTestQuote(['accepted_at' => now()]);

    $params = $component === DeleteQuoteShow::class ? ['quote' => $quote] : [];

    Livewire::test($component, $params)->call('delete', $quote->id);

    expect(Quote::find($quote->id))->toBeNull()
        ->and(Quote::withTrashed()->find($quote->id)->trashed())->toBeTrue();
})->with([
    'show' => DeleteQuoteShow::class,
    'index' => DeleteQuoteIndex::class,
    'board' => DeleteQuoteBoard::class,
]);

test('the web delete route refuses an ordered quote with a 403', function () {
    $quote = deleteTestOrderedQuote();

    $this->delete(route('laravel-crm.quotes.destroy', $quote))->assertForbidden();

    expect(Quote::find($quote->id))->not->toBeNull();
});

test('the API refuses to delete an ordered quote with a 403 and the reason', function () {
    $quote = deleteTestOrderedQuote();

    $user = User::create([
        'name' => 'Quote Delete API User',
        'email' => 'quote-delete-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ]);

    $this->withHeaders([
        'Authorization' => 'Bearer '.$user->createToken('quote-delete-test')->plainTextToken,
        'Accept' => 'application/json',
    ])
        ->deleteJson('/crm/api/v2/quotes/'.$quote->external_id)
        ->assertForbidden()
        ->assertJsonPath('message', deleteTestReason());

    expect(Quote::find($quote->id))->not->toBeNull();
});

test('the show page, index and board offer Delete on an open quote but not on an ordered one', function (string $component) {
    $stages = Pipeline::where('model', Quote::class)->first()->pipelineStages;

    $open = deleteTestQuote(['pipeline_stage_id' => $stages->firstWhere('name', 'Draft')->id]);
    $ordered = deleteTestQuote(['accepted_at' => now(), 'pipeline_stage_id' => $stages->firstWhere('name', 'Accepted')->id]);
    Order::create(['quote_id' => $ordered->id]);

    $html = fn (Quote $quote) => $component === QuoteShow::class
        ? Livewire::test(QuoteShow::class, ['quote' => $quote->fresh()])->html()
        : Livewire::test($component)->html();

    $trigger = fn (Quote $quote) => 'modalDeleteQuote'.$quote->id.'.showModal()';

    expect($html($open))->toContain($trigger($open));

    expect($html($ordered))->not->toContain($trigger($ordered))
        ->toContain(e(deleteTestReason()));
})->with([
    'show' => QuoteShow::class,
    'index' => QuoteIndex::class,
    'board' => QuoteBoard::class,
]);

test('an order whose quote was deleted shows the quote number without a link', function () {
    $quote = deleteTestQuote();
    $order = Order::create(['quote_id' => $quote->id]);
    $quote->delete();

    expect($order->fresh()->quote?->is($quote))->toBeTrue();

    $quoteUrl = route('laravel-crm.quotes.show', $quote);

    expect(Livewire::test(OrderShow::class, ['order' => $order->fresh()])->html())
        ->toContain($quote->quote_id)
        ->not->toContain($quoteUrl);

    expect(Livewire::test(OrderIndex::class)->html())
        ->toContain($quote->quote_id)
        ->not->toContain($quoteUrl);
});

test('an order whose quote is live still links to it', function () {
    $quote = deleteTestQuote();
    $order = Order::create(['quote_id' => $quote->id]);

    expect(Livewire::test(OrderShow::class, ['order' => $order->fresh()])->html())
        ->toContain(route('laravel-crm.quotes.show', $quote));
});
