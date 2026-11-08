<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\QuoteProduct;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function quoteApiUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'Quote API User',
        'email' => 'quote-api-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ], $attributes));
}

function quoteApiHeaders(User $user): array
{
    $token = $user->createToken('quote-api-test')->plainTextToken;

    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ];
}

function createApiProduct(string $name = 'Widget'): Product
{
    $product = Product::create([
        'name' => $name,
    ]);

    return $product->refresh();
}

test('GET /quotes returns 401 when unauthenticated', function () {
    $this->getJson('/crm/api/v2/quotes')->assertStatus(401);
});

test('POST /quotes returns 401 when unauthenticated', function () {
    $this->postJson('/crm/api/v2/quotes', ['title' => 'Hello'])->assertStatus(401);
});

test('POST /quotes creates a quote with nested line items', function () {
    $user = quoteApiUser();
    $organization = Organization::create(['name' => 'Acme Co']);
    $productA = createApiProduct('Widget A');
    $productB = createApiProduct('Widget B');
    // The header tax is the lines' tax at the product's rate; the posted
    // `tax` is ignored.
    $productA->update(['tax_rate' => 10]);
    $productB->update(['tax_rate' => 10]);

    $response = $this->withHeaders(quoteApiHeaders($user))
        ->postJson('/crm/api/v2/quotes', [
            'title' => 'Initial proposal',
            'reference' => 'Q-REF-1',
            'currency' => 'USD',
            'tax' => 99.00,
            'organization_id' => $organization->external_id,
            'user_owner_id' => $user->id,
            'line_items' => [
                [
                    'product_id' => $productA->external_id,
                    'quantity' => 2,
                    'unit_price' => 50.00,
                    'amount' => 100.00,
                    'comments' => 'Bulk',
                ],
                [
                    'product_id' => $productB->external_id,
                    'quantity' => 3,
                    'unit_price' => 50.00,
                    'amount' => 150.00,
                    'comments' => null,
                ],
            ],
        ]);

    $response->assertStatus(201);

    $payload = $response->json('data');
    expect(Str::isUuid($payload['id']))->toBeTrue();
    expect($payload['title'])->toBe('Initial proposal');
    expect($payload['subtotal'])->toEqual(250.00);
    expect($payload['tax'])->toEqual(25.00);
    expect($payload['total'])->toEqual(275.00);
    expect($payload['organization']['id'])->toBe($organization->external_id);
    expect($payload['line_items'])->toHaveCount(2);
    expect($payload['line_items'][0]['product_id'])->toBe($productA->external_id);
    expect($payload['line_items'][0]['quantity'])->toEqual(2.0);
    expect($payload['line_items'][0]['unit_price'])->toEqual(50.00);
    expect($payload['line_items'][0]['amount'])->toEqual(100.00);

    // Verify cents storage on the underlying rows.
    $stored = Quote::where('external_id', $payload['id'])->first();
    expect((int) $stored->subtotal)->toBe(25000);
    expect((int) $stored->total)->toBe(27500);
    expect($stored->quoteProducts)->toHaveCount(2);
    expect((int) $stored->quoteProducts->first()->price)->toBe(5000);
    expect((int) $stored->quoteProducts->first()->amount)->toBe(10000);
});

test('POST /quotes returns 422 when required fields are missing', function () {
    $user = quoteApiUser();

    $this->withHeaders(quoteApiHeaders($user))
        ->postJson('/crm/api/v2/quotes', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

test('POST /quotes returns 422 when a line item product_id is unknown', function () {
    $user = quoteApiUser();

    $this->withHeaders(quoteApiHeaders($user))
        ->postJson('/crm/api/v2/quotes', [
            'title' => 'Bad product',
            'line_items' => [
                [
                    'product_id' => (string) Str::uuid(),
                    'quantity' => 1,
                    'unit_price' => 10.00,
                    'amount' => 10.00,
                ],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['line_items.0.product_id']);
});

test('POST /quotes returns 403 when policy denies create', function () {
    $user = quoteApiUser(['crm_permissions' => json_encode([])]);

    $this->withHeaders(quoteApiHeaders($user))
        ->postJson('/crm/api/v2/quotes', ['title' => 'Forbidden'])
        ->assertStatus(403);
});

test('GET /quotes returns a paginated collection', function () {
    $user = quoteApiUser();

    foreach (range(1, 3) as $i) {
        Quote::create(['title' => 'Quote '.$i]);
    }

    $response = $this->withHeaders(quoteApiHeaders($user))
        ->getJson('/crm/api/v2/quotes?per_page=2');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('meta.total'))->toBe(3);
});

test('GET /quotes/{quote} returns the quote with line items', function () {
    $user = quoteApiUser();
    $product = createApiProduct();

    $created = $this->withHeaders(quoteApiHeaders($user))
        ->postJson('/crm/api/v2/quotes', [
            'title' => 'Find me',
            'line_items' => [[
                'product_id' => $product->external_id,
                'quantity' => 1,
                'unit_price' => 20.00,
                'amount' => 20.00,
            ]],
        ])->json('data');

    $response = $this->withHeaders(quoteApiHeaders($user))
        ->getJson('/crm/api/v2/quotes/'.$created['id']);

    $response->assertOk();
    expect($response->json('data.id'))->toBe($created['id']);
    expect($response->json('data.line_items'))->toHaveCount(1);
});

test('GET /quotes/{quote} returns 404 for unknown UUID', function () {
    $user = quoteApiUser();

    $this->withHeaders(quoteApiHeaders($user))
        ->getJson('/crm/api/v2/quotes/'.((string) Str::uuid()))
        ->assertStatus(404);
});

test('PUT /quotes/{quote} updates line items in place', function () {
    $user = quoteApiUser();
    $product = createApiProduct();

    $created = $this->withHeaders(quoteApiHeaders($user))
        ->postJson('/crm/api/v2/quotes', [
            'title' => 'Original',
            'line_items' => [[
                'product_id' => $product->external_id,
                'quantity' => 1,
                'unit_price' => 10.00,
                'amount' => 10.00,
            ]],
        ])->json('data');

    $lineItemId = $created['line_items'][0]['id'];

    $response = $this->withHeaders(quoteApiHeaders($user))
        ->putJson('/crm/api/v2/quotes/'.$created['id'], [
            'title' => 'Updated',
            'line_items' => [[
                'id' => $lineItemId,
                'product_id' => $product->external_id,
                'quantity' => 4,
                'unit_price' => 10.00,
                'amount' => 40.00,
            ]],
        ]);

    $response->assertOk();
    expect($response->json('data.title'))->toBe('Updated');
    expect($response->json('data.line_items'))->toHaveCount(1);
    expect($response->json('data.line_items.0.id'))->toBe($lineItemId);
    expect($response->json('data.line_items.0.quantity'))->toEqual(4.0);

    // QuoteProduct row was updated, not duplicated.
    $stored = QuoteProduct::where('external_id', $lineItemId)->first();
    expect($stored->quantity)->toBe(4.0);
    expect((int) $stored->amount)->toBe(4000);
});

test('DELETE /quotes/{quote} soft-deletes the quote', function () {
    $user = quoteApiUser();
    $quote = Quote::create(['title' => 'Toast']);

    $this->withHeaders(quoteApiHeaders($user))
        ->deleteJson('/crm/api/v2/quotes/'.$quote->external_id)
        ->assertStatus(204);

    expect(Quote::query()->where('external_id', $quote->external_id)->exists())->toBeFalse();
    expect(Quote::withTrashed()->where('external_id', $quote->external_id)->exists())->toBeTrue();
});

test('DELETE /quotes/{quote} returns 403 when policy denies delete', function () {
    $user = quoteApiUser(['crm_permissions' => json_encode([])]);
    $quote = Quote::create(['title' => 'Untouchable']);

    $this->withHeaders(quoteApiHeaders($user))
        ->deleteJson('/crm/api/v2/quotes/'.$quote->external_id)
        ->assertStatus(403);
});

/*
 * Decimal quantities. quantity is decimal(15,3) so a product can be sold by
 * weight; the API widened from `integer|min:1` to `numeric|min:0.001` and its
 * response type changed from int to number.
 */

test('POST /quotes accepts a fractional line item quantity', function () {
    $user = quoteApiUser();
    $product = createApiProduct('By weight');

    $response = $this->withHeaders(quoteApiHeaders($user))
        ->postJson('/crm/api/v2/quotes', [
            'title' => 'Sold by weight',
            'currency' => 'USD',
            'line_items' => [
                ['product_id' => $product->external_id, 'quantity' => 3.5, 'unit_price' => 1.99, 'amount' => 6.97],
            ],
        ]);

    $response->assertStatus(201);

    expect($response->json('data.line_items.0.quantity'))->toBe(3.5);

    $stored = Quote::where('external_id', $response->json('data.id'))->first();
    expect($stored->quoteProducts->first()->quantity)->toBe(3.5);
});

test('POST /quotes returns 422 for a quantity finer than 3 decimal places', function () {
    $user = quoteApiUser();
    $product = createApiProduct('By weight');

    $this->withHeaders(quoteApiHeaders($user))
        ->postJson('/crm/api/v2/quotes', [
            'title' => 'Too precise',
            'currency' => 'USD',
            'line_items' => [
                ['product_id' => $product->external_id, 'quantity' => 3.5555, 'unit_price' => 1.99, 'amount' => 7.09],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['line_items.0.quantity']);
});

test('POST /quotes returns 422 for a zero quantity', function () {
    $user = quoteApiUser();
    $product = createApiProduct('By weight');

    // min:0.001 rather than gt:0 - 0.0001 would round to 0 on store and the
    // services' isPositive() guards would silently discard the line.
    $this->withHeaders(quoteApiHeaders($user))
        ->postJson('/crm/api/v2/quotes', [
            'title' => 'Nothing',
            'currency' => 'USD',
            'line_items' => [
                ['product_id' => $product->external_id, 'quantity' => 0, 'unit_price' => 1.99, 'amount' => 0],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['line_items.0.quantity']);
});

function apiQuote(array $attributes = []): Quote
{
    return Quote::create(['title' => 'Sample', 'currency' => 'USD', ...$attributes]);
}

function storedDate(Quote $quote, string $column): ?string
{
    return $quote->fresh()->getRawOriginal($column);
}

describe('the expiry', function () {
    beforeEach(function () {
        $this->withHeaders(quoteApiHeaders(quoteApiUser()));
    });

    it('applies the offset it is sent with', function (string $sent, string $stored) {
        $quote = apiQuote();

        $this->patchJson('/crm/api/v2/quotes/'.$quote->external_id, ['expire_at' => $sent])
            ->assertOk()
            ->assertJsonPath('data.expire_at', Carbon::parse($stored)->toIso8601String());

        expect(storedDate($quote, 'expire_at'))->toBe($stored);
    })->with([
        'behind UTC' => ['2026-11-06T20:00:00-05:00', '2026-11-07 01:00:00'],
        'ahead of UTC' => ['2026-11-20T00:00:00+10:00', '2026-11-19 14:00:00'],
        'in UTC' => ['2026-11-20T09:00:00Z', '2026-11-20 09:00:00'],
    ]);

    it('enforces that moment rather than the clock time as sent', function () {
        $quote = apiQuote();
        $this->patchJson('/crm/api/v2/quotes/'.$quote->external_id, ['expire_at' => '2026-11-06T20:00:00-05:00'])->assertOk();

        $this->travelTo('2026-11-07 00:59:00');
        expect($quote->fresh()->isExpired())->toBeFalse();

        $this->travelTo('2026-11-07 01:01:00');
        expect($quote->fresh()->isExpired())->toBeTrue();
    });

    it('takes midnight in the app timezone as the whole day', function () {
        $quote = apiQuote();
        $this->patchJson('/crm/api/v2/quotes/'.$quote->external_id, ['expire_at' => '2026-11-20T00:00:00+00:00'])->assertOk();

        $this->travelTo('2026-11-20 23:00:00');

        expect($quote->fresh()->isExpired())->toBeFalse();
    });

    it('converts to the app timezone rather than UTC', function () {
        $timezone = date_default_timezone_get();
        config()->set('app.timezone', 'Australia/Sydney');
        date_default_timezone_set('Australia/Sydney');

        try {
            $quote = apiQuote();

            $this->patchJson('/crm/api/v2/quotes/'.$quote->external_id, ['expire_at' => '2026-11-20T09:00:00+00:00'])->assertOk();

            expect(storedDate($quote, 'expire_at'))->toBe('2026-11-20 20:00:00');
        } finally {
            config()->set('app.timezone', $timezone);
            date_default_timezone_set($timezone);
        }
    });

    it('reads back correctly when the config and PHP timezones differ', function () {
        config()->set('app.timezone', 'Australia/Sydney');
        $quote = apiQuote();

        $this->patchJson('/crm/api/v2/quotes/'.$quote->external_id, ['expire_at' => '2026-11-20T09:00:00+11:00'])->assertOk();

        expect(storedDate($quote, 'expire_at'))->toBe('2026-11-19 22:00:00');
    });

    it('takes a value past the end of 9999 as the end of 9999', function () {
        $quote = apiQuote();

        $this->patchJson('/crm/api/v2/quotes/'.$quote->external_id, ['expire_at' => '9999-12-31T23:59:59-05:00'])->assertOk();

        expect(storedDate($quote, 'expire_at'))->toBe('9999-12-31 23:59:59')
            ->and($quote->fresh()->isExpired())->toBeFalse();
    });

    it('applies the offset on a new quote too', function () {
        $this->postJson('/crm/api/v2/quotes', [
            'title' => 'Sample',
            'organization_id' => Organization::create(['name' => 'Acme Co'])->external_id,
            'expire_at' => '2026-11-06T20:00:00-05:00',
        ])->assertCreated();

        expect(Quote::first()->expire_at->format('Y-m-d H:i:s'))->toBe('2026-11-07 01:00:00');
    });

    it('is left alone by an update that omits it', function (string $stored) {
        $quote = apiQuote(['expire_at' => $stored]);

        $this->patchJson('/crm/api/v2/quotes/'.$quote->external_id, ['title' => 'Changed'])->assertOk();

        expect(storedDate($quote, 'expire_at'))->toBe($stored)
            ->and($quote->fresh()->title)->toBe('Changed');
    })->with(['date-only' => '2026-11-20 00:00:00', 'timed' => '2026-11-20 09:00:00']);

    it('is cleared by an explicit null', function () {
        $quote = apiQuote(['expire_at' => '2026-11-20 09:00:00']);

        $this->patchJson('/crm/api/v2/quotes/'.$quote->external_id, ['expire_at' => null])->assertOk();

        expect(storedDate($quote, 'expire_at'))->toBeNull();
    });
});

describe('an older blank date', function () {
    beforeEach(function () {
        $this->withHeaders(quoteApiHeaders(quoteApiUser()));
    });

    it('is not turned into a real date by an update that omits it', function (string $column, string $blank) {
        $quote = apiQuote();
        Quote::whereKey($quote->id)->toBase()->update([$column => $blank]);

        $this->patchJson('/crm/api/v2/quotes/'.$quote->external_id, ['title' => 'Changed'])->assertOk();

        expect(storedDate($quote, $column))->toBeNull()
            ->and($quote->fresh()->isExpired())->toBeFalse();
    })->with(['expire_at', 'issue_at'])->with(['an empty string' => '', 'a zero date' => '0000-00-00 00:00:00']);
});

describe('the expiry and issue date order', function () {
    beforeEach(function () {
        $this->withHeaders(quoteApiHeaders(quoteApiUser()));
        $this->organization = Organization::create(['name' => 'Acme Co']);
    });

    it('rejects an expiry on a day before the issue date', function () {
        $this->postJson('/crm/api/v2/quotes', [
            'title' => 'Sample',
            'organization_id' => $this->organization->external_id,
            'issue_at' => '2026-11-08T00:00:00Z',
            'expire_at' => '2026-11-01T00:00:00Z',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['expire_at'])
            ->assertJsonMissingValidationErrors(['issue_at']);

        expect(Quote::count())->toBe(0);
    });

    it('accepts an expiry on the same day at an earlier time', function () {
        $this->postJson('/crm/api/v2/quotes', [
            'title' => 'Sample',
            'organization_id' => $this->organization->external_id,
            'issue_at' => '2026-11-08T14:23:00Z',
            'expire_at' => '2026-11-08T00:00:00Z',
        ])->assertCreated();
    });

    it('compares the expiry on the day it will be stored', function () {
        // 02:00 at +05:00 is 21:00 the day before in UTC, the app timezone.
        $this->postJson('/crm/api/v2/quotes', [
            'title' => 'Sample',
            'organization_id' => $this->organization->external_id,
            'issue_at' => '2026-11-08T00:00:00Z',
            'expire_at' => '2026-11-08T02:00:00+05:00',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['expire_at']);
    });

    it('puts the error on the issue date when only that is sent', function () {
        $quote = apiQuote(['issue_at' => '2026-11-01 00:00:00', 'expire_at' => '2026-11-08 00:00:00']);

        $this->putJson('/crm/api/v2/quotes/'.$quote->external_id, ['issue_at' => '2026-11-09T00:00:00Z'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['issue_at'])
            ->assertJsonMissingValidationErrors(['expire_at']);

        expect(storedDate($quote, 'issue_at'))->toBe('2026-11-01 00:00:00');
    });

    it('checks an expiry sent alone against the stored issue date', function () {
        $quote = apiQuote(['issue_at' => '2026-11-08 00:00:00', 'expire_at' => '2026-11-30 00:00:00']);

        $this->putJson('/crm/api/v2/quotes/'.$quote->external_id, ['expire_at' => '2026-11-01T00:00:00Z'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['expire_at']);
    });

    it('does not stop an unrelated update to a quote already stored out of order', function () {
        $quote = apiQuote(['issue_at' => '2026-11-08 00:00:00', 'expire_at' => '2026-11-01 00:00:00']);

        $this->putJson('/crm/api/v2/quotes/'.$quote->external_id, ['title' => 'Changed'])
            ->assertOk();

        expect($quote->fresh()->title)->toBe('Changed');
    });

    it('does not stop an unrelated update to a quote with no dates', function () {
        $quote = apiQuote();

        $this->putJson('/crm/api/v2/quotes/'.$quote->external_id, ['title' => 'Changed'])
            ->assertOk();

        expect($quote->fresh()->title)->toBe('Changed');
    });
});
