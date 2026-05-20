<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function personApiUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'Person API User',
        'email' => 'person-api-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ], $attributes));
}

function personApiHeaders(User $user): array
{
    $token = $user->createToken('person-api-test')->plainTextToken;

    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ];
}

test('GET /people returns 401 when unauthenticated', function () {
    $response = $this->getJson('/api/crm/v2/people');

    $response->assertStatus(401);
});

test('POST /people returns 401 when unauthenticated', function () {
    $response = $this->postJson('/api/crm/v2/people', ['first_name' => 'Jane']);

    $response->assertStatus(401);
});

test('GET /people returns paginated collection with UUID ids', function () {
    $user = personApiUser();

    foreach (range(1, 3) as $i) {
        Person::create(['first_name' => 'Person'.$i, 'last_name' => 'Test']);
    }

    $response = $this->withHeaders(personApiHeaders($user))
        ->getJson('/api/crm/v2/people');

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'first_name', 'last_name', 'created_at', 'updated_at'],
        ],
        'links',
        'meta' => ['current_page', 'per_page', 'total'],
    ]);

    $payload = $response->json();
    expect($payload['data'])->toHaveCount(3);
    expect(Str::isUuid($payload['data'][0]['id']))->toBeTrue();
});

test('GET /people honors per_page', function () {
    $user = personApiUser();

    foreach (range(1, 5) as $i) {
        Person::create(['first_name' => 'P'.$i]);
    }

    $response = $this->withHeaders(personApiHeaders($user))
        ->getJson('/api/crm/v2/people?per_page=2');

    $response->assertOk();
    expect($response->json('meta.per_page'))->toBe(2);
    expect($response->json('meta.total'))->toBe(5);
});

test('GET /people filters by user_owner_id', function () {
    $user = personApiUser();
    $other = personApiUser();

    Person::create(['first_name' => 'Mine', 'user_owner_id' => $user->id]);
    Person::create(['first_name' => 'Mine2', 'user_owner_id' => $user->id]);
    Person::create(['first_name' => 'Theirs', 'user_owner_id' => $other->id]);

    $response = $this->withHeaders(personApiHeaders($user))
        ->getJson('/api/crm/v2/people?user_owner_id='.$user->id);

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
});

test('POST /people creates a person and returns 201', function () {
    $user = personApiUser();

    $response = $this->withHeaders(personApiHeaders($user))
        ->postJson('/api/crm/v2/people', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'description' => 'A great contact',
        ]);

    $response->assertStatus(201);
    $payload = $response->json('data');

    expect($payload['first_name'])->toBe('Jane');
    expect($payload['last_name'])->toBe('Doe');
    expect($payload['name'])->toBe('Jane Doe');
    expect(Str::isUuid($payload['id']))->toBeTrue();
});

test('POST /people resolves UUIDs for organization and labels', function () {
    $user = personApiUser();
    $organization = Organization::create(['name' => 'Acme Co']);
    $label = Label::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'VIP',
        'hex' => 'ff0000',
    ]);

    $response = $this->withHeaders(personApiHeaders($user))
        ->postJson('/api/crm/v2/people', [
            'first_name' => 'Linked',
            'organization_id' => $organization->external_id,
            'user_owner_id' => $user->id,
            'labels' => [$label->external_id],
        ]);

    $response->assertStatus(201);

    $payload = $response->json('data');
    expect($payload['organization']['id'])->toBe($organization->external_id);
    expect($payload['owner']['id'])->toBe($user->id);
    expect($payload['labels'])->toHaveCount(1);
    expect($payload['labels'][0]['id'])->toBe($label->external_id);
});

test('POST /people returns 422 when first_name is missing', function () {
    $user = personApiUser();

    $response = $this->withHeaders(personApiHeaders($user))
        ->postJson('/api/crm/v2/people', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['first_name']);
});

test('POST /people returns 422 when organization_id is not a UUID', function () {
    $user = personApiUser();

    $response = $this->withHeaders(personApiHeaders($user))
        ->postJson('/api/crm/v2/people', [
            'first_name' => 'Bad',
            'organization_id' => 'not-a-uuid',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['organization_id']);
});

test('POST /people returns 422 when organization UUID does not exist', function () {
    $user = personApiUser();

    $response = $this->withHeaders(personApiHeaders($user))
        ->postJson('/api/crm/v2/people', [
            'first_name' => 'Bad',
            'organization_id' => (string) Str::uuid(),
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['organization_id']);
});

test('POST /people returns 403 when policy denies create', function () {
    $user = personApiUser(['crm_permissions' => json_encode([])]);

    $response = $this->withHeaders(personApiHeaders($user))
        ->postJson('/api/crm/v2/people', [
            'first_name' => 'Forbidden',
        ]);

    $response->assertStatus(403);
});

test('GET /people/{person} returns 403 when policy denies view', function () {
    $user = personApiUser(['crm_permissions' => json_encode([])]);
    $person = Person::create(['first_name' => 'Hidden']);

    $response = $this->withHeaders(personApiHeaders($user))
        ->getJson('/api/crm/v2/people/'.$person->external_id);

    $response->assertStatus(403);
});

test('GET /people/{person} returns the person by UUID', function () {
    $user = personApiUser();
    $person = Person::create(['first_name' => 'Findable', 'last_name' => 'Smith']);

    $response = $this->withHeaders(personApiHeaders($user))
        ->getJson('/api/crm/v2/people/'.$person->external_id);

    $response->assertOk();
    expect($response->json('data.id'))->toBe($person->external_id);
    expect($response->json('data.first_name'))->toBe('Findable');
});

test('GET /people/{person} returns 404 for unknown UUID', function () {
    $user = personApiUser();

    $response = $this->withHeaders(personApiHeaders($user))
        ->getJson('/api/crm/v2/people/'.((string) Str::uuid()));

    $response->assertStatus(404);
});

test('PUT /people/{person} updates a person', function () {
    $user = personApiUser();
    $person = Person::create(['first_name' => 'Original']);

    $response = $this->withHeaders(personApiHeaders($user))
        ->putJson('/api/crm/v2/people/'.$person->external_id, [
            'first_name' => 'Updated',
            'last_name' => 'Person',
        ]);

    $response->assertOk();
    expect($response->json('data.first_name'))->toBe('Updated');

    $fresh = $person->fresh();
    expect($fresh->first_name)->toBe('Updated');
    expect($fresh->last_name)->toBe('Person');
});

test('PUT /people/{person} can link to an organization by UUID', function () {
    $user = personApiUser();
    $person = Person::create(['first_name' => 'Orig']);
    $organization = Organization::create(['name' => 'Linked Co']);

    $response = $this->withHeaders(personApiHeaders($user))
        ->putJson('/api/crm/v2/people/'.$person->external_id, [
            'first_name' => 'Orig',
            'organization_id' => $organization->external_id,
        ]);

    $response->assertOk();
    expect($response->json('data.organization.id'))->toBe($organization->external_id);
    expect($person->fresh()->organization_id)->toBe($organization->id);
});

test('PUT /people/{person} preserves seeded phones, emails, and addresses when omitted', function () {
    $user = personApiUser();
    $person = Person::create(['first_name' => 'Original']);

    $phone = $person->phones()->create([
        'external_id' => (string) Str::uuid(),
        'number' => '+1-555-0100',
        'type' => 'work',
        'primary' => 1,
    ]);

    $email = $person->emails()->create([
        'external_id' => (string) Str::uuid(),
        'address' => 'kept@example.com',
        'type' => 'work',
        'primary' => 1,
    ]);

    $address = $person->addresses()->create([
        'external_id' => (string) Str::uuid(),
        'line1' => '1 Main St',
        'city' => 'Townsville',
        'country' => 'AU',
        'primary' => 1,
    ]);

    $response = $this->withHeaders(personApiHeaders($user))
        ->putJson('/api/crm/v2/people/'.$person->external_id, [
            'first_name' => 'Renamed',
        ]);

    $response->assertOk();

    $fresh = $person->fresh();
    expect($fresh->first_name)->toBe('Renamed');
    expect($fresh->phones()->count())->toBe(1);
    expect($fresh->phones()->first()->id)->toBe($phone->id);
    expect($fresh->emails()->count())->toBe(1);
    expect($fresh->emails()->first()->id)->toBe($email->id);
    expect($fresh->addresses()->count())->toBe(1);
    expect($fresh->addresses()->first()->id)->toBe($address->id);
});

test('DELETE /people/{person} soft-deletes the person', function () {
    $user = personApiUser();
    $person = Person::create(['first_name' => 'Toast']);

    $response = $this->withHeaders(personApiHeaders($user))
        ->deleteJson('/api/crm/v2/people/'.$person->external_id);

    $response->assertStatus(204);

    expect(Person::query()->where('external_id', $person->external_id)->exists())->toBeFalse();
    expect(Person::withTrashed()->where('external_id', $person->external_id)->exists())->toBeTrue();

    $followUp = $this->withHeaders(personApiHeaders($user))
        ->getJson('/api/crm/v2/people/'.$person->external_id);
    $followUp->assertStatus(404);
});

test('DELETE /people/{person} returns 403 when policy denies delete', function () {
    $user = personApiUser(['crm_permissions' => json_encode([])]);
    $person = Person::create(['first_name' => 'Untouchable']);

    $response = $this->withHeaders(personApiHeaders($user))
        ->deleteJson('/api/crm/v2/people/'.$person->external_id);

    $response->assertStatus(403);
    expect(Person::query()->where('external_id', $person->external_id)->exists())->toBeTrue();
});
