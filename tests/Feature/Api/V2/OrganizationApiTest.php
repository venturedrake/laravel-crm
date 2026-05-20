<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function organizationApiUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'Org API User',
        'email' => 'org-api-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ], $attributes));
}

function organizationApiHeaders(User $user): array
{
    $token = $user->createToken('org-api-test')->plainTextToken;

    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ];
}

test('GET /organizations returns 401 when unauthenticated', function () {
    $response = $this->getJson('/api/crm/v2/organizations');

    $response->assertStatus(401);
});

test('POST /organizations returns 401 when unauthenticated', function () {
    $response = $this->postJson('/api/crm/v2/organizations', ['name' => 'Hello']);

    $response->assertStatus(401);
});

test('GET /organizations returns paginated collection with UUID ids and ISO timestamps', function () {
    $user = organizationApiUser();

    foreach (range(1, 3) as $i) {
        Organization::create(['name' => 'Org '.$i]);
    }

    $response = $this->withHeaders(organizationApiHeaders($user))
        ->getJson('/api/crm/v2/organizations');

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'name', 'created_at', 'updated_at'],
        ],
        'links',
        'meta' => ['current_page', 'per_page', 'total'],
    ]);

    $payload = $response->json();
    expect($payload['data'])->toHaveCount(3);
    expect(Str::isUuid($payload['data'][0]['id']))->toBeTrue();
    expect($payload['data'][0]['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});

test('GET /organizations honors per_page pagination', function () {
    $user = organizationApiUser();

    foreach (range(1, 5) as $i) {
        Organization::create(['name' => 'Org '.$i]);
    }

    $response = $this->withHeaders(organizationApiHeaders($user))
        ->getJson('/api/crm/v2/organizations?per_page=2');

    $response->assertOk();
    $payload = $response->json();

    expect($payload['data'])->toHaveCount(2);
    expect($payload['meta']['per_page'])->toBe(2);
    expect($payload['meta']['total'])->toBe(5);
});

test('GET /organizations filters by user_owner_id', function () {
    $user = organizationApiUser();
    $otherOwner = organizationApiUser();

    Organization::create(['name' => 'Mine', 'user_owner_id' => $user->id]);
    Organization::create(['name' => 'Mine 2', 'user_owner_id' => $user->id]);
    Organization::create(['name' => 'Theirs', 'user_owner_id' => $otherOwner->id]);

    $response = $this->withHeaders(organizationApiHeaders($user))
        ->getJson('/api/crm/v2/organizations?user_owner_id='.$user->id);

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
});

test('POST /organizations creates an organization and returns 201 with UUID id and divided money fields', function () {
    $user = organizationApiUser();

    $response = $this->withHeaders(organizationApiHeaders($user))
        ->postJson('/api/crm/v2/organizations', [
            'name' => 'Acme Co',
            'description' => 'A solid customer',
            'vat_number' => 'VAT-12345',
            'number_of_employees' => 50,
            'annual_revenue' => 1500000.50,
            'total_money_raised' => 2500000.00,
            'linkedin' => 'https://linkedin.com/company/acme',
        ]);

    $response->assertStatus(201);
    $payload = $response->json('data');

    expect($payload['name'])->toBe('Acme Co');
    expect($payload['vat_number'])->toBe('VAT-12345');
    expect($payload['number_of_employees'])->toBe(50);
    expect($payload['annual_revenue'])->toEqual(1500000.50);
    expect($payload['total_money_raised'])->toEqual(2500000.00);
    expect(Str::isUuid($payload['id']))->toBeTrue();

    // Raw DB amounts are stored as integer cents.
    $stored = Organization::where('external_id', $payload['id'])->first();
    expect((int) $stored->getRawOriginal('annual_revenue'))->toBe(150000050);
    expect((int) $stored->getRawOriginal('total_money_raised'))->toBe(250000000);
});

test('POST /organizations returns 422 when name is missing', function () {
    $user = organizationApiUser();

    $response = $this->withHeaders(organizationApiHeaders($user))
        ->postJson('/api/crm/v2/organizations', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);
});

test('POST /organizations returns 422 when number_of_employees is not integer', function () {
    $user = organizationApiUser();

    $response = $this->withHeaders(organizationApiHeaders($user))
        ->postJson('/api/crm/v2/organizations', [
            'name' => 'Bad data',
            'number_of_employees' => 'lots',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['number_of_employees']);
});

test('POST /organizations returns 422 when annual_revenue is negative', function () {
    $user = organizationApiUser();

    $response = $this->withHeaders(organizationApiHeaders($user))
        ->postJson('/api/crm/v2/organizations', [
            'name' => 'Negative',
            'annual_revenue' => -100,
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['annual_revenue']);
});

test('POST /organizations returns 422 when user_owner_id does not exist', function () {
    $user = organizationApiUser();

    $response = $this->withHeaders(organizationApiHeaders($user))
        ->postJson('/api/crm/v2/organizations', [
            'name' => 'Phantom owner',
            'user_owner_id' => 9999999,
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['user_owner_id']);
});

test('POST /organizations returns 403 when policy denies create', function () {
    $user = organizationApiUser(['crm_permissions' => json_encode([])]);

    $response = $this->withHeaders(organizationApiHeaders($user))
        ->postJson('/api/crm/v2/organizations', [
            'name' => 'Forbidden',
        ]);

    $response->assertStatus(403);
});

test('GET /organizations/{organization} returns 403 when policy denies view', function () {
    $user = organizationApiUser(['crm_permissions' => json_encode([])]);
    $organization = Organization::create(['name' => 'Hidden']);

    $response = $this->withHeaders(organizationApiHeaders($user))
        ->getJson('/api/crm/v2/organizations/'.$organization->external_id);

    $response->assertStatus(403);
});

test('GET /organizations/{organization} returns the organization by UUID', function () {
    $user = organizationApiUser();
    $organization = Organization::create(['name' => 'Findable']);

    $response = $this->withHeaders(organizationApiHeaders($user))
        ->getJson('/api/crm/v2/organizations/'.$organization->external_id);

    $response->assertOk();
    expect($response->json('data.id'))->toBe($organization->external_id);
    expect($response->json('data.name'))->toBe('Findable');
});

test('GET /organizations/{organization} returns 404 for unknown UUID', function () {
    $user = organizationApiUser();

    $response = $this->withHeaders(organizationApiHeaders($user))
        ->getJson('/api/crm/v2/organizations/'.((string) Str::uuid()));

    $response->assertStatus(404);
});

test('PUT /organizations/{organization} updates an organization', function () {
    $user = organizationApiUser();
    $organization = Organization::create(['name' => 'Original']);

    $response = $this->withHeaders(organizationApiHeaders($user))
        ->putJson('/api/crm/v2/organizations/'.$organization->external_id, [
            'name' => 'Updated',
            'annual_revenue' => 250.75,
        ]);

    $response->assertOk();
    expect($response->json('data.name'))->toBe('Updated');
    expect($response->json('data.annual_revenue'))->toEqual(250.75);

    $fresh = $organization->fresh();
    expect($fresh->name)->toBe('Updated');
    expect((int) $fresh->getRawOriginal('annual_revenue'))->toBe(25075);
});

test('PUT /organizations/{organization} preserves seeded phones, emails, and addresses when omitted', function () {
    $user = organizationApiUser();
    $organization = Organization::create(['name' => 'Original']);

    $phone = $organization->phones()->create([
        'external_id' => (string) Str::uuid(),
        'number' => '+1-555-0200',
        'type' => 'work',
        'primary' => 1,
    ]);

    $email = $organization->emails()->create([
        'external_id' => (string) Str::uuid(),
        'address' => 'kept@example.com',
        'type' => 'work',
        'primary' => 1,
    ]);

    $address = $organization->addresses()->create([
        'external_id' => (string) Str::uuid(),
        'line1' => '1 Main St',
        'city' => 'Townsville',
        'country' => 'AU',
        'primary' => 1,
    ]);

    $response = $this->withHeaders(organizationApiHeaders($user))
        ->putJson('/api/crm/v2/organizations/'.$organization->external_id, [
            'name' => 'Renamed',
        ]);

    $response->assertOk();

    $fresh = $organization->fresh();
    expect($fresh->name)->toBe('Renamed');
    expect($fresh->phones()->count())->toBe(1);
    expect($fresh->phones()->first()->id)->toBe($phone->id);
    expect($fresh->emails()->count())->toBe(1);
    expect($fresh->emails()->first()->id)->toBe($email->id);
    expect($fresh->addresses()->count())->toBe(1);
    expect($fresh->addresses()->first()->id)->toBe($address->id);
});

test('DELETE /organizations/{organization} soft-deletes the organization', function () {
    $user = organizationApiUser();
    $organization = Organization::create(['name' => 'Toast']);

    $response = $this->withHeaders(organizationApiHeaders($user))
        ->deleteJson('/api/crm/v2/organizations/'.$organization->external_id);

    $response->assertStatus(204);

    expect(Organization::query()->where('external_id', $organization->external_id)->exists())->toBeFalse();
    expect(Organization::withTrashed()->where('external_id', $organization->external_id)->exists())->toBeTrue();

    $followUp = $this->withHeaders(organizationApiHeaders($user))
        ->getJson('/api/crm/v2/organizations/'.$organization->external_id);
    $followUp->assertStatus(404);
});

test('DELETE /organizations/{organization} returns 403 when policy denies delete', function () {
    $user = organizationApiUser(['crm_permissions' => json_encode([])]);
    $organization = Organization::create(['name' => 'Untouchable']);

    $response = $this->withHeaders(organizationApiHeaders($user))
        ->deleteJson('/api/crm/v2/organizations/'.$organization->external_id);

    $response->assertStatus(403);
    expect(Organization::query()->where('external_id', $organization->external_id)->exists())->toBeTrue();
});
