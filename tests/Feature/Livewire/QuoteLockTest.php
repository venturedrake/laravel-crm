<?php

/*
 * An accepted quote, or one with an order made from it, is read-only.
 *
 * QU-3001 was accepted and ordered as ORD-2070, then edited: its lines and
 * total no longer matched the order. The Edit button was only hidden on the
 * index and board, the edit URL still worked, and nothing below the form
 * checked. The rule now lives on the quote (isLocked / lockReason) and the
 * service refuses a locked quote, so the form, the edit URL and the API are
 * all held to it. Unaccepting an ordered quote is refused too, since that
 * would unlock it. Accept, reject and board moves still work.
 */

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteBoard;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteEdit;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteIndex;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteShow;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\QuoteProduct;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Services\QuoteService;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

class LockQuoteShow extends QuoteShow
{
    public function render()
    {
        return '<div></div>';
    }
}

class LockQuoteIndex extends QuoteIndex
{
    public function render()
    {
        return '<div></div>';
    }
}

class LockQuoteBoard extends QuoteBoard
{
    public function render()
    {
        return '<div></div>';
    }
}

class LockQuoteEdit extends QuoteEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

beforeEach(function () {
    $this->actingAsUserWithPermissions(['view crm quotes', 'edit crm quotes']);

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

    $this->product = Product::create(['name' => 'Widget']);
});

/**
 * A quote of 2 × 100.00 made through the service, as the form makes one.
 */
function lockTestQuote($product, array $attributes = []): Quote
{
    $quote = app(QuoteService::class)->create(new Request([
        'title' => 'Fit-out',
        'currency' => 'USD',
        'products' => [['id' => $product->id, 'quantity' => 2, 'unit_price' => 100, 'amount' => 200, 'comments' => null]],
    ]), null, Organization::create(['name' => 'Customer Co']));

    $quote->update($attributes);

    return $quote->fresh();
}

/**
 * An edit that would change the quote's only line to 5 × 100.00.
 */
function lockTestEdit(Quote $quote, $product): Request
{
    return new Request([
        'title' => 'Changed',
        'currency' => 'USD',
        'products' => [[
            'quote_product_id' => $quote->quoteProducts->first()->id,
            'id' => $product->id,
            'quantity' => 5,
            'unit_price' => 100,
            'amount' => 500,
            'comments' => null,
        ]],
    ]);
}

function expectQuoteUnchanged(Quote $quote): void
{
    $fresh = $quote->fresh();

    expect($fresh->title)->toBe('Fit-out')
        ->and((int) $fresh->getRawOriginal('total'))->toBe(20000)
        ->and($fresh->quoteProducts)->toHaveCount(1)
        ->and(QuoteProduct::where('quote_id', $quote->id)->first()->quantity)->toBe(2.0)
        ->and((int) QuoteProduct::where('quote_id', $quote->id)->first()->getRawOriginal('amount'))->toBe(20000);
}

function toastText($component): string
{
    // Mary shows a toast through a js effect.
    return json_encode($component->effects['xjs'] ?? [], JSON_UNESCAPED_UNICODE);
}

test('a quote is locked once accepted or ordered, and unlocked again by unaccepting', function () {
    $quote = lockTestQuote($this->product);

    expect($quote->isLocked())->toBeFalse()
        ->and($quote->lockReason())->toBeNull();

    $quote->update(['accepted_at' => now()]);

    expect($quote->fresh()->isLocked())->toBeTrue()
        ->and($quote->fresh()->lockReason())->toBe(ucfirst(__('laravel-crm::lang.quote_locked_accepted')));

    Livewire::test(LockQuoteShow::class, ['quote' => $quote->fresh()])->call('unaccept', $quote->id);

    expect($quote->fresh()->accepted_at)->toBeNull()
        ->and($quote->fresh()->isLocked())->toBeFalse();

    Order::create(['quote_id' => $quote->id]);

    expect($quote->fresh()->isLocked())->toBeTrue()
        ->and($quote->fresh()->lockReason())->toBe(ucfirst(__('laravel-crm::lang.quote_locked_ordered')));
});

test('a soft-deleted order does not lock the quote', function () {
    $quote = lockTestQuote($this->product);
    Order::create(['quote_id' => $quote->id])->delete();

    expect($quote->fresh()->hasOrders())->toBeFalse()
        ->and($quote->fresh()->isLocked())->toBeFalse();
});

test('the edit URL of an accepted quote redirects to the quote with the reason', function () {
    $quote = lockTestQuote($this->product, ['accepted_at' => now()]);

    $this->get(route('laravel-crm.quotes.edit', $quote))
        ->assertRedirect(route('laravel-crm.quotes.show', $quote));

    expect(json_encode(session()->all(), JSON_UNESCAPED_UNICODE))->toContain(ucfirst(__('laravel-crm::lang.quote_locked_accepted')));
});

test('the edit URL of an ordered quote redirects to the quote', function () {
    $quote = lockTestQuote($this->product, ['accepted_at' => now()]);
    Order::create(['quote_id' => $quote->id]);

    $this->get(route('laravel-crm.quotes.edit', $quote))
        ->assertRedirect(route('laravel-crm.quotes.show', $quote));
});

test('the edit form refuses to mount an accepted quote', function () {
    $quote = lockTestQuote($this->product, ['accepted_at' => now()]);

    Livewire::test(QuoteEdit::class, ['quote' => $quote])
        ->assertRedirect(route('laravel-crm.quotes.show', $quote));
});

test('saving an edit form opened before the quote was accepted is refused with a message', function () {
    $quote = lockTestQuote($this->product);

    $component = Livewire::test(LockQuoteEdit::class, ['quote' => $quote]);

    $quote->update(['accepted_at' => now()]);

    $component
        ->set('title', 'Changed')
        ->set('products', [[
            'quote_product_id' => $quote->quoteProducts->first()->id,
            'id' => $this->product->id,
            'quantity' => 5,
            'unit_price' => 100,
            'amount' => 500,
            'comments' => null,
        ]])
        ->call('save')
        ->assertNoRedirect();

    expect(toastText($component))->toContain(ucfirst(__('laravel-crm::lang.quote_locked_accepted')));
    expectQuoteUnchanged($quote);
});

test('the service refuses to update an accepted quote', function () {
    $quote = lockTestQuote($this->product, ['accepted_at' => now()]);

    expect(fn () => app(QuoteService::class)->update(lockTestEdit($quote, $this->product), $quote))
        ->toThrow(ValidationException::class, ucfirst(__('laravel-crm::lang.quote_locked_accepted')));

    expectQuoteUnchanged($quote);
});

test('the service refuses to update an ordered quote', function () {
    $quote = lockTestQuote($this->product, ['accepted_at' => now()]);
    Order::create(['quote_id' => $quote->id]);

    expect(fn () => app(QuoteService::class)->update(lockTestEdit($quote, $this->product), $quote))
        ->toThrow(ValidationException::class, ucfirst(__('laravel-crm::lang.quote_locked_ordered')));

    expectQuoteUnchanged($quote);
});

test('an unaccepted quote with no order can still be edited', function () {
    $quote = lockTestQuote($this->product);

    app(QuoteService::class)->update(lockTestEdit($quote, $this->product), $quote);

    expect($quote->fresh()->title)->toBe('Changed')
        ->and((int) $quote->fresh()->getRawOriginal('total'))->toBe(50000);
});

describe('a time of day set on the expiry', function () {
    function saveTitle(Quote $quote, $test, ?string $expiry = null): void
    {
        $component = Livewire::test(LockQuoteEdit::class, ['quote' => $quote])
            ->set('title', 'Changed')
            ->set('products', [[
                'quote_product_id' => $quote->quoteProducts->first()->id,
                'id' => $test->product->id,
                'quantity' => 5,
                'unit_price' => 100,
                'amount' => 500,
                'comments' => null,
            ]]);

        if ($expiry) {
            $component->set('expire_at', $expiry);
        }

        $component->call('save')->assertHasNoErrors();
    }

    it('survives a save that leaves the date alone', function () {
        $this->travelTo('2026-11-06 10:00:00');
        $quote = lockTestQuote($this->product, ['expire_at' => '2026-11-06 09:00:00']);

        saveTitle($quote, $this);

        expect($quote->fresh()->title)->toBe('Changed')
            ->and($quote->fresh()->expire_at->format('H:i'))->toBe('09:00')
            ->and($quote->fresh()->isExpired())->toBeTrue();
    });

    it('gives way to a new date', function () {
        $this->travelTo('2026-11-06 10:00:00');
        $quote = lockTestQuote($this->product, ['expire_at' => '2026-11-06 09:00:00']);

        saveTitle($quote, $this, '2026-11-20');

        expect($quote->fresh()->expire_at->format('Y-m-d H:i'))->toBe('2026-11-20 00:00')
            ->and($quote->fresh()->isExpired())->toBeFalse();
    });
});

describe('clearing a date', function () {
    function editProducts(Quote $quote, $test): array
    {
        return [[
            'quote_product_id' => $quote->quoteProducts->first()->id,
            'id' => $test->product->id,
            'quantity' => 5,
            'unit_price' => 100,
            'amount' => 500,
            'comments' => null,
        ]];
    }

    it('stores nothing, which every database accepts', function (string $field) {
        $quote = lockTestQuote($this->product, ['issue_at' => '2026-11-01', 'expire_at' => '2026-11-20']);

        Livewire::test(LockQuoteEdit::class, ['quote' => $quote])
            ->set('products', editProducts($quote, $this))
            ->set($field, '')
            ->call('save')
            ->assertHasNoErrors();

        expect($quote->fresh()->getRawOriginal($field))->toBeNull();
    })->with(['issue_at', 'expire_at']);

    it('stores nothing for a blank date on a new quote', function () {
        $quote = app(QuoteService::class)->create(new Request([
            'title' => 'Fit-out',
            'currency' => 'USD',
            'issue_at' => '',
            'expire_at' => '',
        ]), null, Organization::create(['name' => 'Customer Co']));

        expect($quote->fresh()->getRawOriginal('issue_at'))->toBeNull()
            ->and($quote->fresh()->getRawOriginal('expire_at'))->toBeNull();
    });

    it('repairs an older blank when the form is saved', function (string $field, string $blank) {
        $quote = lockTestQuote($this->product);
        Quote::whereKey($quote->id)->toBase()->update([$field => $blank]);

        Livewire::test(LockQuoteEdit::class, ['quote' => $quote->fresh()])
            ->assertSet($field, null)
            ->set('products', editProducts($quote, $this))
            ->call('save')
            ->assertHasNoErrors();

        expect($quote->fresh()->getRawOriginal($field))->toBeNull();
    })->with(['expire_at', 'issue_at'])->with(['an empty string' => '', 'a zero date' => '0000-00-00 00:00:00']);
});

test('the edit form saves an unaccepted quote with no order', function () {
    $quote = lockTestQuote($this->product);

    Livewire::test(LockQuoteEdit::class, ['quote' => $quote])
        ->set('title', 'Changed')
        ->set('products', [[
            'quote_product_id' => $quote->quoteProducts->first()->id,
            'id' => $this->product->id,
            'quantity' => 5,
            'unit_price' => 100,
            'amount' => 500,
            'comments' => null,
        ]])
        ->call('save')
        ->assertRedirect(route('laravel-crm.quotes.index'));

    expect($quote->fresh()->title)->toBe('Changed')
        ->and((int) $quote->fresh()->getRawOriginal('total'))->toBe(50000);
});

function lockTestApiHeaders(): array
{
    $user = User::create([
        'name' => 'Quote Lock API User',
        'email' => 'quote-lock-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ]);

    return [
        'Authorization' => 'Bearer '.$user->createToken('quote-lock-test')->plainTextToken,
        'Accept' => 'application/json',
    ];
}

function lockTestApiPayload(Quote $quote, $product): array
{
    return [
        'title' => 'Changed',
        'line_items' => [[
            'id' => $quote->quoteProducts->first()->external_id,
            'product_id' => $product->fresh()->external_id,
            'quantity' => 5,
            'unit_price' => 100,
            'amount' => 500,
        ]],
    ];
}

test('the API refuses to update an accepted quote with a 422', function () {
    $quote = lockTestQuote($this->product, ['accepted_at' => now()]);

    $this->withHeaders(lockTestApiHeaders())
        ->putJson('/crm/api/v2/quotes/'.$quote->external_id, lockTestApiPayload($quote, $this->product))
        ->assertStatus(422)
        ->assertJsonPath('errors.quote.0', ucfirst(__('laravel-crm::lang.quote_locked_accepted')));

    expectQuoteUnchanged($quote);
});

test('the API refuses to update an ordered quote with a 422', function () {
    $quote = lockTestQuote($this->product, ['accepted_at' => now()]);
    Order::create(['quote_id' => $quote->id]);

    $this->withHeaders(lockTestApiHeaders())
        ->putJson('/crm/api/v2/quotes/'.$quote->external_id, lockTestApiPayload($quote, $this->product))
        ->assertStatus(422)
        ->assertJsonPath('errors.quote.0', ucfirst(__('laravel-crm::lang.quote_locked_ordered')));

    expectQuoteUnchanged($quote);
});

test('an ordered quote cannot be unaccepted from the show page, index or board', function (string $component) {
    $quote = lockTestQuote($this->product, ['accepted_at' => now()]);
    Order::create(['quote_id' => $quote->id]);

    $params = $component === LockQuoteShow::class ? ['quote' => $quote] : [];

    $test = Livewire::test($component, $params)->call('unaccept', $quote->id);

    expect($quote->fresh()->accepted_at)->not->toBeNull()
        ->and(toastText($test))->toContain(ucfirst(__('laravel-crm::lang.quote_locked_ordered')));
})->with([
    'show' => LockQuoteShow::class,
    'index' => LockQuoteIndex::class,
    'board' => LockQuoteBoard::class,
]);

test('a locked quote can still be moved on the board', function () {
    $quote = lockTestQuote($this->product, ['accepted_at' => now()]);
    Order::create(['quote_id' => $quote->id]);

    $stage = Pipeline::where('model', Quote::class)->first()->pipelineStages()->where('name', 'Rejected')->first();

    Livewire::test(LockQuoteBoard::class)
        ->call('onStageChanged', $quote->id, $stage->id, [], [])
        ->assertOk();

    expect($quote->fresh()->pipeline_stage_id)->toBe($stage->id);
});

test('the edit policy follows the lock but update does not', function () {
    $quote = lockTestQuote($this->product);
    $user = auth()->user();

    expect($user->can('edit', $quote))->toBeTrue();

    $quote->update(['accepted_at' => now()]);

    expect($user->can('edit', $quote->fresh()))->toBeFalse()
        ->and($user->can('update', $quote->fresh()))->toBeTrue();
});

test('the show page, index and board offer Edit on an open quote but not on a locked one', function (string $component) {
    $stages = Pipeline::where('model', Quote::class)->first()->pipelineStages;

    $open = lockTestQuote($this->product, ['pipeline_stage_id' => $stages->firstWhere('name', 'Draft')->id]);
    $accepted = lockTestQuote($this->product, ['accepted_at' => now(), 'pipeline_stage_id' => $stages->firstWhere('name', 'Accepted')->id]);
    $ordered = lockTestQuote($this->product, ['accepted_at' => now(), 'pipeline_stage_id' => $stages->firstWhere('name', 'Accepted')->id]);
    Order::create(['quote_id' => $ordered->id]);

    $html = fn (Quote $quote) => $component === QuoteShow::class
        ? Livewire::test(QuoteShow::class, ['quote' => $quote->fresh()])->html()
        : Livewire::test($component)->html();

    $editUrl = fn (Quote $quote) => '"'.url(route('laravel-crm.quotes.edit', $quote)).'"';

    expect($html($open))->toContain($editUrl($open));

    foreach ([$accepted, $ordered] as $locked) {
        expect($html($locked))->not->toContain($editUrl($locked))
            ->toContain(e($locked->fresh()->lockReason()));
    }
})->with([
    'show' => QuoteShow::class,
    'index' => QuoteIndex::class,
    'board' => QuoteBoard::class,
]);

test('the show page offers Unaccept on an accepted quote but not on an ordered one', function () {
    $accepted = lockTestQuote($this->product, ['accepted_at' => now()]);
    $ordered = lockTestQuote($this->product, ['accepted_at' => now()]);
    Order::create(['quote_id' => $ordered->id]);

    expect(Livewire::test(QuoteShow::class, ['quote' => $accepted])->html())
        ->toContain('unaccept('.$accepted->id.')');

    expect(Livewire::test(QuoteShow::class, ['quote' => $ordered->fresh()])->html())
        ->not->toContain('unaccept('.$ordered->id.')')
        ->toContain(ucfirst(__('laravel-crm::lang.ordered')));
});
