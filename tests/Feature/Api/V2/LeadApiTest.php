<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\LeadStatus;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function leadApiUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'Lead API User',
        'email' => 'lead-api-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ], $attributes));
}

function leadApiHeaders(User $user): array
{
    $token = $user->createToken('lead-api-test')->plainTextToken;

    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ];
}

function seedLeadStatus(): void
{
    if (! LeadStatus::query()->where('id', 1)->exists()) {
        LeadStatus::query()->insert([
            'id' => 1,
            'name' => 'New',
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

beforeEach(function () {
    seedLeadStatus();
});

test('GET /leads returns 401 when unauthenticated', function () {
    $response = $this->getJson('/api/crm/v2/leads');

    $response->assertStatus(401);
});

test('POST /leads returns 401 when unauthenticated', function () {
    $response = $this->postJson('/api/crm/v2/leads', ['title' => 'Hello']);

    $response->assertStatus(401);
});

test('GET /leads returns paginated collection with UUID ids and ISO timestamps', function () {
    $user = leadApiUser();

    foreach (range(1, 3) as $i) {
        Lead::create([
            'title' => 'Lead '.$i,
            'amount' => $i * 100, // raw cents, since setAmountAttribute multiplies; we use direct create with attribute mutator
        ]);
    }

    $response = $this->withHeaders(leadApiHeaders($user))
        ->getJson('/api/crm/v2/leads');

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'lead_id', 'title', 'amount', 'currency', 'created_at', 'updated_at'],
        ],
        'links',
        'meta' => ['current_page', 'per_page', 'total'],
    ]);

    $payload = $response->json();
    expect($payload['data'])->toHaveCount(3);
    expect($payload['data'][0]['id'])->toBeString();
    expect(Str::isUuid($payload['data'][0]['id']))->toBeTrue();
    expect($payload['data'][0]['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});

test('GET /leads honors per_page pagination', function () {
    $user = leadApiUser();

    foreach (range(1, 5) as $i) {
        Lead::create(['title' => 'Lead '.$i]);
    }

    $response = $this->withHeaders(leadApiHeaders($user))
        ->getJson('/api/crm/v2/leads?per_page=2');

    $response->assertOk();
    $payload = $response->json();

    expect($payload['data'])->toHaveCount(2);
    expect($payload['meta']['per_page'])->toBe(2);
    expect($payload['meta']['total'])->toBe(5);
});

test('GET /leads filters by user_owner_id', function () {
    $user = leadApiUser();
    $otherOwner = leadApiUser();

    Lead::create(['title' => 'Mine', 'user_owner_id' => $user->id]);
    Lead::create(['title' => 'Mine 2', 'user_owner_id' => $user->id]);
    Lead::create(['title' => 'Theirs', 'user_owner_id' => $otherOwner->id]);

    $response = $this->withHeaders(leadApiHeaders($user))
        ->getJson('/api/crm/v2/leads?user_owner_id='.$user->id);

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
});

test('POST /leads creates a lead and returns 201 with UUID id and divided amount', function () {
    $user = leadApiUser();

    $response = $this->withHeaders(leadApiHeaders($user))
        ->postJson('/api/crm/v2/leads', [
            'title' => 'Acme deal',
            'description' => 'A solid opportunity',
            'amount' => 1500.50,
            'currency' => 'USD',
        ]);

    $response->assertStatus(201);
    $payload = $response->json('data');

    expect($payload['title'])->toBe('Acme deal');
    expect($payload['amount'])->toEqual(1500.50);
    expect($payload['currency'])->toBe('USD');
    expect(Str::isUuid($payload['id']))->toBeTrue();
    expect($payload['lead_id'])->toMatch('/\d+/');

    // Sanity check: amount in DB is stored as integer cents.
    $stored = Lead::where('external_id', $payload['id'])->first();
    expect((int) $stored->amount)->toBe(150050);
});

test('POST /leads resolves UUIDs for related entities', function () {
    $user = leadApiUser();
    $person = Person::create(['first_name' => 'John', 'last_name' => 'Smith']);
    $organization = Organization::create(['name' => 'Acme Co']);
    $label = Label::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'VIP',
        'hex' => 'ff0000',
    ]);

    $response = $this->withHeaders(leadApiHeaders($user))
        ->postJson('/api/crm/v2/leads', [
            'title' => 'Linked deal',
            'person_id' => $person->external_id,
            'organization_id' => $organization->external_id,
            'user_owner_id' => $user->id,
            'labels' => [$label->external_id],
        ]);

    $response->assertStatus(201);

    $payload = $response->json('data');
    expect($payload['person']['id'])->toBe($person->external_id);
    expect($payload['organization']['id'])->toBe($organization->external_id);
    expect($payload['owner']['id'])->toBe($user->id);
    expect($payload['labels'])->toHaveCount(1);
    expect($payload['labels'][0]['id'])->toBe($label->external_id);
});

test('POST /leads returns 422 when required fields are missing', function () {
    $user = leadApiUser();

    $response = $this->withHeaders(leadApiHeaders($user))
        ->postJson('/api/crm/v2/leads', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['title']);
});

test('POST /leads returns 422 when person_id is not a UUID', function () {
    $user = leadApiUser();

    $response = $this->withHeaders(leadApiHeaders($user))
        ->postJson('/api/crm/v2/leads', [
            'title' => 'Bad data',
            'person_id' => 'not-a-uuid',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['person_id']);
});

test('POST /leads returns 422 when person UUID does not exist', function () {
    $user = leadApiUser();

    $response = $this->withHeaders(leadApiHeaders($user))
        ->postJson('/api/crm/v2/leads', [
            'title' => 'Missing person',
            'person_id' => (string) Str::uuid(),
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['person_id']);
});

test('POST /leads returns 422 when expected_close is not ISO-8601', function () {
    $user = leadApiUser();

    $response = $this->withHeaders(leadApiHeaders($user))
        ->postJson('/api/crm/v2/leads', [
            'title' => 'Bad date',
            'expected_close' => '07/15/2026 10:00',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['expected_close']);
});

test('POST /leads accepts a valid ISO-8601 expected_close', function () {
    $user = leadApiUser();

    $response = $this->withHeaders(leadApiHeaders($user))
        ->postJson('/api/crm/v2/leads', [
            'title' => 'With date',
            'expected_close' => '2026-07-15T10:00:00Z',
        ]);

    $response->assertStatus(201);
    expect($response->json('data.expected_close'))->toMatch('/^2026-07-15T10:00:00/');
});

test('POST /leads returns 403 when policy denies create', function () {
    $user = leadApiUser(['crm_permissions' => json_encode([])]);

    $response = $this->withHeaders(leadApiHeaders($user))
        ->postJson('/api/crm/v2/leads', [
            'title' => 'Forbidden',
        ]);

    $response->assertStatus(403);
});

test('GET /leads/{lead} returns 403 when policy denies view', function () {
    $user = leadApiUser(['crm_permissions' => json_encode([])]);
    $lead = Lead::create(['title' => 'Hidden']);

    $response = $this->withHeaders(leadApiHeaders($user))
        ->getJson('/api/crm/v2/leads/'.$lead->external_id);

    $response->assertStatus(403);
});

test('GET /leads/{lead} returns the lead by UUID', function () {
    $user = leadApiUser();
    $lead = Lead::create(['title' => 'Findable']);

    $response = $this->withHeaders(leadApiHeaders($user))
        ->getJson('/api/crm/v2/leads/'.$lead->external_id);

    $response->assertOk();
    expect($response->json('data.id'))->toBe($lead->external_id);
    expect($response->json('data.title'))->toBe('Findable');
});

test('GET /leads/{lead} returns 404 for unknown UUID', function () {
    $user = leadApiUser();

    $response = $this->withHeaders(leadApiHeaders($user))
        ->getJson('/api/crm/v2/leads/'.((string) Str::uuid()));

    $response->assertStatus(404);
});

test('PUT /leads/{lead} updates a lead', function () {
    $user = leadApiUser();
    $lead = Lead::create(['title' => 'Original', 'amount' => 100]);

    $response = $this->withHeaders(leadApiHeaders($user))
        ->putJson('/api/crm/v2/leads/'.$lead->external_id, [
            'title' => 'Updated',
            'amount' => 250.75,
        ]);

    $response->assertOk();
    expect($response->json('data.title'))->toBe('Updated');
    expect($response->json('data.amount'))->toEqual(250.75);

    $fresh = $lead->fresh();
    expect($fresh->title)->toBe('Updated');
    expect((int) $fresh->amount)->toBe(25075);
});

test('PUT /leads/{lead} preserves the attached person when person_id is omitted', function () {
    $user = leadApiUser();
    $person = Person::create(['first_name' => 'Keep', 'last_name' => 'Me']);
    $lead = Lead::create(['title' => 'Original', 'person_id' => $person->id]);

    $response = $this->withHeaders(leadApiHeaders($user))
        ->putJson('/api/crm/v2/leads/'.$lead->external_id, [
            'title' => 'Only title changes',
        ]);

    $response->assertOk();
    expect($response->json('data.title'))->toBe('Only title changes');

    $fresh = $lead->fresh();
    expect($fresh->title)->toBe('Only title changes');
    expect($fresh->person_id)->toBe($person->id);
});

test('PUT /leads/{lead} preserves the attached organization when organization_id is omitted', function () {
    $user = leadApiUser();
    $organization = Organization::create(['name' => 'Keep Me Co']);
    $lead = Lead::create(['title' => 'Original', 'organization_id' => $organization->id]);

    $response = $this->withHeaders(leadApiHeaders($user))
        ->putJson('/api/crm/v2/leads/'.$lead->external_id, [
            'title' => 'Only title changes',
        ]);

    $response->assertOk();

    $fresh = $lead->fresh();
    expect($fresh->title)->toBe('Only title changes');
    expect($fresh->organization_id)->toBe($organization->id);
});

test('PUT /leads/{lead} clears person/organization when explicitly set to null', function () {
    $user = leadApiUser();
    $person = Person::create(['first_name' => 'Drop', 'last_name' => 'Me']);
    $organization = Organization::create(['name' => 'Drop Me Co']);
    $lead = Lead::create([
        'title' => 'Original',
        'person_id' => $person->id,
        'organization_id' => $organization->id,
    ]);

    $response = $this->withHeaders(leadApiHeaders($user))
        ->putJson('/api/crm/v2/leads/'.$lead->external_id, [
            'person_id' => null,
            'organization_id' => null,
        ]);

    $response->assertOk();

    $fresh = $lead->fresh();
    expect($fresh->person_id)->toBeNull();
    expect($fresh->organization_id)->toBeNull();
});

test('DELETE /leads/{lead} soft-deletes the lead', function () {
    $user = leadApiUser();
    $lead = Lead::create(['title' => 'Toast']);

    $response = $this->withHeaders(leadApiHeaders($user))
        ->deleteJson('/api/crm/v2/leads/'.$lead->external_id);

    $response->assertStatus(204);

    expect(Lead::query()->where('external_id', $lead->external_id)->exists())->toBeFalse();
    expect(Lead::withTrashed()->where('external_id', $lead->external_id)->exists())->toBeTrue();

    // The same UUID is now unreachable through the API (soft-deleted records are excluded).
    $followUp = $this->withHeaders(leadApiHeaders($user))
        ->getJson('/api/crm/v2/leads/'.$lead->external_id);
    $followUp->assertStatus(404);
});

test('DELETE /leads/{lead} returns 403 when policy denies delete', function () {
    $user = leadApiUser(['crm_permissions' => json_encode([])]);
    $lead = Lead::create(['title' => 'Untouchable']);

    $response = $this->withHeaders(leadApiHeaders($user))
        ->deleteJson('/api/crm/v2/leads/'.$lead->external_id);

    $response->assertStatus(403);
    expect(Lead::query()->where('external_id', $lead->external_id)->exists())->toBeTrue();
});
