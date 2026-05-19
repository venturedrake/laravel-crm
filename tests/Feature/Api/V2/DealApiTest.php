<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function dealApiUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'Deal API User',
        'email' => 'deal-api-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ], $attributes));
}

function dealApiHeaders(User $user): array
{
    $token = $user->createToken('deal-api-test')->plainTextToken;

    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ];
}

test('GET /deals returns 401 when unauthenticated', function () {
    $response = $this->getJson('/api/crm/v2/deals');

    $response->assertStatus(401);
});

test('POST /deals returns 401 when unauthenticated', function () {
    $response = $this->postJson('/api/crm/v2/deals', ['title' => 'Hello']);

    $response->assertStatus(401);
});

test('GET /deals returns paginated collection with UUID ids', function () {
    $user = dealApiUser();

    foreach (range(1, 3) as $i) {
        Deal::create(['title' => 'Deal '.$i]);
    }

    $response = $this->withHeaders(dealApiHeaders($user))
        ->getJson('/api/crm/v2/deals');

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'deal_id', 'title', 'amount', 'currency', 'created_at', 'updated_at'],
        ],
        'links',
        'meta' => ['current_page', 'per_page', 'total'],
    ]);

    $payload = $response->json();
    expect($payload['data'])->toHaveCount(3);
    expect(Str::isUuid($payload['data'][0]['id']))->toBeTrue();
});

test('GET /deals honors per_page', function () {
    $user = dealApiUser();

    foreach (range(1, 5) as $i) {
        Deal::create(['title' => 'Deal '.$i]);
    }

    $response = $this->withHeaders(dealApiHeaders($user))
        ->getJson('/api/crm/v2/deals?per_page=2');

    $response->assertOk();
    expect($response->json('meta.per_page'))->toBe(2);
    expect($response->json('meta.total'))->toBe(5);
});

test('GET /deals filters by user_owner_id', function () {
    $user = dealApiUser();
    $other = dealApiUser();

    Deal::create(['title' => 'Mine', 'user_owner_id' => $user->id]);
    Deal::create(['title' => 'Mine 2', 'user_owner_id' => $user->id]);
    Deal::create(['title' => 'Theirs', 'user_owner_id' => $other->id]);

    $response = $this->withHeaders(dealApiHeaders($user))
        ->getJson('/api/crm/v2/deals?user_owner_id='.$user->id);

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
});

test('POST /deals creates a deal and returns 201 with UUID id and divided amount', function () {
    $user = dealApiUser();

    $response = $this->withHeaders(dealApiHeaders($user))
        ->postJson('/api/crm/v2/deals', [
            'title' => 'Big Deal',
            'description' => 'Solid opportunity',
            'amount' => 2500.75,
            'currency' => 'USD',
        ]);

    $response->assertStatus(201);
    $payload = $response->json('data');

    expect($payload['title'])->toBe('Big Deal');
    expect($payload['amount'])->toEqual(2500.75);
    expect($payload['currency'])->toBe('USD');
    expect(Str::isUuid($payload['id']))->toBeTrue();
    expect($payload['deal_id'])->toMatch('/\d+/');

    $stored = Deal::where('external_id', $payload['id'])->first();
    expect((int) $stored->amount)->toBe(250075);
});

test('POST /deals resolves UUIDs for related entities', function () {
    $user = dealApiUser();
    $person = Person::create(['first_name' => 'John', 'last_name' => 'Smith']);
    $organization = Organization::create(['name' => 'Acme Co']);
    $lead = Lead::create(['title' => 'Linked Lead']);
    $label = Label::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Hot',
        'hex' => 'ff0000',
    ]);

    $response = $this->withHeaders(dealApiHeaders($user))
        ->postJson('/api/crm/v2/deals', [
            'title' => 'Linked Deal',
            'person_id' => $person->external_id,
            'organization_id' => $organization->external_id,
            'lead_id' => $lead->external_id,
            'user_owner_id' => $user->id,
            'labels' => [$label->external_id],
        ]);

    $response->assertStatus(201);

    $payload = $response->json('data');
    expect($payload['person']['id'])->toBe($person->external_id);
    expect($payload['organization']['id'])->toBe($organization->external_id);
    expect($payload['lead']['id'])->toBe($lead->external_id);
    expect($payload['owner']['id'])->toBe($user->id);
    expect($payload['labels'])->toHaveCount(1);
    expect($payload['labels'][0]['id'])->toBe($label->external_id);
});

test('POST /deals returns 422 when required fields are missing', function () {
    $user = dealApiUser();

    $response = $this->withHeaders(dealApiHeaders($user))
        ->postJson('/api/crm/v2/deals', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['title']);
});

test('POST /deals returns 422 when person_id is not a UUID', function () {
    $user = dealApiUser();

    $response = $this->withHeaders(dealApiHeaders($user))
        ->postJson('/api/crm/v2/deals', [
            'title' => 'Bad data',
            'person_id' => 'not-a-uuid',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['person_id']);
});

test('POST /deals returns 422 when lead UUID does not exist', function () {
    $user = dealApiUser();

    $response = $this->withHeaders(dealApiHeaders($user))
        ->postJson('/api/crm/v2/deals', [
            'title' => 'Bad lead',
            'lead_id' => (string) Str::uuid(),
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['lead_id']);
});

test('POST /deals returns 422 when expected_close is not ISO-8601', function () {
    $user = dealApiUser();

    $response = $this->withHeaders(dealApiHeaders($user))
        ->postJson('/api/crm/v2/deals', [
            'title' => 'Bad date',
            'expected_close' => '07/15/2026 10:00',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['expected_close']);
});

test('POST /deals accepts a valid ISO-8601 expected_close', function () {
    $user = dealApiUser();

    $response = $this->withHeaders(dealApiHeaders($user))
        ->postJson('/api/crm/v2/deals', [
            'title' => 'With date',
            'expected_close' => '2026-07-15T10:00:00Z',
        ]);

    $response->assertStatus(201);
    expect($response->json('data.expected_close'))->toMatch('/^2026-07-15T10:00:00/');
});

test('POST /deals returns 403 when policy denies create', function () {
    $user = dealApiUser(['crm_permissions' => json_encode([])]);

    $response = $this->withHeaders(dealApiHeaders($user))
        ->postJson('/api/crm/v2/deals', [
            'title' => 'Forbidden',
        ]);

    $response->assertStatus(403);
});

test('GET /deals/{deal} returns 403 when policy denies view', function () {
    $user = dealApiUser(['crm_permissions' => json_encode([])]);
    $deal = Deal::create(['title' => 'Hidden']);

    $response = $this->withHeaders(dealApiHeaders($user))
        ->getJson('/api/crm/v2/deals/'.$deal->external_id);

    $response->assertStatus(403);
});

test('GET /deals/{deal} returns the deal by UUID', function () {
    $user = dealApiUser();
    $deal = Deal::create(['title' => 'Findable']);

    $response = $this->withHeaders(dealApiHeaders($user))
        ->getJson('/api/crm/v2/deals/'.$deal->external_id);

    $response->assertOk();
    expect($response->json('data.id'))->toBe($deal->external_id);
    expect($response->json('data.title'))->toBe('Findable');
});

test('GET /deals/{deal} returns 404 for unknown UUID', function () {
    $user = dealApiUser();

    $response = $this->withHeaders(dealApiHeaders($user))
        ->getJson('/api/crm/v2/deals/'.((string) Str::uuid()));

    $response->assertStatus(404);
});

test('PUT /deals/{deal} updates a deal', function () {
    $user = dealApiUser();
    $deal = Deal::create(['title' => 'Original', 'amount' => 100]);

    $response = $this->withHeaders(dealApiHeaders($user))
        ->putJson('/api/crm/v2/deals/'.$deal->external_id, [
            'title' => 'Updated',
            'amount' => 999.99,
        ]);

    $response->assertOk();
    expect($response->json('data.title'))->toBe('Updated');
    expect($response->json('data.amount'))->toEqual(999.99);

    $fresh = $deal->fresh();
    expect($fresh->title)->toBe('Updated');
    expect((int) $fresh->amount)->toBe(99999);
});

test('DELETE /deals/{deal} soft-deletes the deal', function () {
    $user = dealApiUser();
    $deal = Deal::create(['title' => 'Toast']);

    $response = $this->withHeaders(dealApiHeaders($user))
        ->deleteJson('/api/crm/v2/deals/'.$deal->external_id);

    $response->assertStatus(204);

    expect(Deal::query()->where('external_id', $deal->external_id)->exists())->toBeFalse();
    expect(Deal::withTrashed()->where('external_id', $deal->external_id)->exists())->toBeTrue();

    $followUp = $this->withHeaders(dealApiHeaders($user))
        ->getJson('/api/crm/v2/deals/'.$deal->external_id);
    $followUp->assertStatus(404);
});

test('DELETE /deals/{deal} returns 403 when policy denies delete', function () {
    $user = dealApiUser(['crm_permissions' => json_encode([])]);
    $deal = Deal::create(['title' => 'Untouchable']);

    $response = $this->withHeaders(dealApiHeaders($user))
        ->deleteJson('/api/crm/v2/deals/'.$deal->external_id);

    $response->assertStatus(403);
    expect(Deal::query()->where('external_id', $deal->external_id)->exists())->toBeTrue();
});
