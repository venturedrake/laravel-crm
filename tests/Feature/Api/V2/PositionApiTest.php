<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Position;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function positionApiUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'Position API User',
        'email' => 'position-api-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ], $attributes));
}

function positionApiHeaders(User $user): array
{
    return [
        'Authorization' => 'Bearer '.$user->createToken('position-api-test')->plainTextToken,
        'Accept' => 'application/json',
    ];
}

function positionApiPerson(string $firstName, ?Organization $organization = null): Person
{
    return Person::create([
        'first_name' => $firstName,
        'last_name' => 'Tester',
        'organization_id' => $organization?->id,
    ]);
}

function positionApiPosition(Person $person, Organization $organization, string $title, array $attributes = []): Position
{
    return Position::create(array_merge([
        'person_id' => $person->id,
        'organization_id' => $organization->id,
        'title' => $title,
        'start_date' => '2026-01-01',
    ], $attributes))->refresh();
}

function personPositionsUrl(Person $person, ?Position $position = null): string
{
    return '/crm/api/v2/people/'.$person->external_id.'/positions'.($position ? '/'.$position->external_id : '');
}

function organizationPositionsUrl(Organization $organization, ?Position $position = null): string
{
    return '/crm/api/v2/organizations/'.$organization->external_id.'/positions'.($position ? '/'.$position->external_id : '');
}

test('positions under a person support index, store, show, update and destroy', function () {
    $headers = positionApiHeaders(positionApiUser());
    $organization = Organization::create(['name' => 'JumpSkipAreo']);
    $person = positionApiPerson('Casey', $organization);

    $store = $this->withHeaders($headers)->postJson(personPositionsUrl($person), [
        'title' => ' CFO ',
        'description' => 'Runs finance',
    ]);

    $store->assertStatus(201);
    $id = $store->json('data.id');
    expect(Str::isUuid($id))->toBeTrue();
    expect($store->json('data.title'))->toBe('CFO');
    // Start date defaults to today, organisation to the person's own.
    expect($store->json('data.start_date'))->toBe(now()->toDateString());
    expect($store->json('data.end_date'))->toBeNull();
    expect($store->json('data.current'))->toBeTrue();
    expect($store->json('data.organization.id'))->toBe($organization->external_id);
    expect($store->json('data.person.id'))->toBe($person->external_id);

    $position = Position::where('external_id', $id)->first();

    $index = $this->withHeaders($headers)->getJson(personPositionsUrl($person));
    $index->assertOk();
    expect(collect($index->json('data'))->pluck('id')->all())->toBe([$id]);

    $this->withHeaders($headers)->getJson(personPositionsUrl($person, $position))
        ->assertOk()
        ->assertJsonPath('data.description', 'Runs finance');

    $update = $this->withHeaders($headers)->putJson(personPositionsUrl($person, $position), [
        'title' => 'Chief Financial Officer',
        'end_date' => now()->addDay()->toDateString(),
    ]);
    $update->assertOk();
    expect($update->json('data.title'))->toBe('Chief Financial Officer');
    // Fields that were not sent are left alone.
    expect($update->json('data.description'))->toBe('Runs finance');
    expect($update->json('data.end_date'))->toBe(now()->addDay()->toDateString());

    // An explicit null makes it current again.
    $this->withHeaders($headers)->patchJson(personPositionsUrl($person, $position), ['end_date' => null])
        ->assertOk()
        ->assertJsonPath('data.end_date', null);

    $this->withHeaders($headers)->deleteJson(personPositionsUrl($person, $position))->assertNoContent();
    expect(Position::find($position->id))->toBeNull();
    expect(Position::withTrashed()->find($position->id))->not->toBeNull();
    $this->withHeaders($headers)->getJson(personPositionsUrl($person, $position))->assertStatus(404);
});

test('a position can be created under an organisation for an existing person', function () {
    $headers = positionApiHeaders(positionApiUser());
    $organization = Organization::create(['name' => 'JumpSkipAreo']);
    $person = positionApiPerson('Gale');

    $store = $this->withHeaders($headers)->postJson(organizationPositionsUrl($organization), [
        'person_id' => $person->external_id,
        'title' => 'Board member',
        'start_date' => '2026-03-01',
    ]);

    $store->assertStatus(201);
    expect($store->json('data.start_date'))->toBe('2026-03-01');
    expect($store->json('data.person.id'))->toBe($person->external_id);
    // The person had no organisation, so the position's becomes theirs.
    expect((int) $person->fresh()->organization_id)->toBe($organization->id);

    $index = $this->withHeaders($headers)->getJson(organizationPositionsUrl($organization));
    expect($index->json('data.0.title'))->toBe('Board member');
});

test('creating a position ends the ones it replaces unless end_conflicting is false', function () {
    $headers = positionApiHeaders(positionApiUser());
    $organization = Organization::create(['name' => 'JumpSkipAreo']);
    $old = positionApiPerson('Old', $organization);
    $new = positionApiPerson('New', $organization);
    $oldCfo = positionApiPosition($old, $organization, 'CFO');
    $newAnalyst = positionApiPosition($new, $organization, 'Analyst');
    $gm = positionApiPosition($old, $organization, 'GM');

    $this->withHeaders($headers)->postJson(organizationPositionsUrl($organization), [
        'person_id' => $new->external_id,
        'title' => 'cfo',
        'start_date' => '2026-06-01',
    ])->assertStatus(201);

    // The earlier holder's CFO and the new holder's own earlier position end.
    expect($oldCfo->fresh()->end_date->toDateString())->toBe('2026-06-01');
    expect($newAnalyst->fresh()->end_date->toDateString())->toBe('2026-06-01');
    expect($gm->fresh()->end_date)->toBeNull();

    $third = positionApiPerson('Third', $organization);
    $this->withHeaders($headers)->postJson(personPositionsUrl($third), [
        'title' => 'GM',
        'end_conflicting' => false,
    ])->assertStatus(201);

    expect($gm->fresh()->end_date)->toBeNull();
});

test('?current filters the list to current or past positions', function () {
    $headers = positionApiHeaders(positionApiUser());
    $organization = Organization::create(['name' => 'JumpSkipAreo']);
    $person = positionApiPerson('Casey', $organization);
    positionApiPosition($person, $organization, 'Analyst', ['end_date' => '2026-02-01']);
    positionApiPosition($person, $organization, 'CFO', ['start_date' => '2026-02-01']);

    $titles = fn (string $query) => collect(
        $this->withHeaders($headers)->getJson(personPositionsUrl($person).$query)->json('data')
    )->pluck('title')->all();

    expect($titles(''))->toBe(['CFO', 'Analyst']);
    expect($titles('?current=1'))->toBe(['CFO']);
    expect($titles('?current=0'))->toBe(['Analyst']);
});

test('position writes are validated', function () {
    $headers = positionApiHeaders(positionApiUser());
    $organization = Organization::create(['name' => 'JumpSkipAreo']);
    $loner = positionApiPerson('Loner');
    $member = positionApiPerson('Member', $organization);
    $position = positionApiPosition($member, $organization, 'CFO', ['start_date' => '2026-05-01']);

    // No title, and no organisation to fall back on.
    $this->withHeaders($headers)->postJson(personPositionsUrl($loner), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'organization_id']);

    $this->withHeaders($headers)->postJson(organizationPositionsUrl($organization), ['title' => 'CEO'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['person_id']);

    $this->withHeaders($headers)->postJson(organizationPositionsUrl($organization), [
        'person_id' => (string) Str::uuid(),
        'title' => 'CEO',
    ])->assertStatus(422)->assertJsonValidationErrors(['person_id']);

    $this->withHeaders($headers)->postJson(personPositionsUrl($member), [
        'title' => 'CEO',
        'start_date' => '2026-05-01',
        'end_date' => '2026-04-01',
    ])->assertStatus(422)->assertJsonValidationErrors(['end_date']);

    // An end date alone is still held to the stored start date.
    $this->withHeaders($headers)->patchJson(personPositionsUrl($member, $position), ['end_date' => '2026-04-01'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['end_date']);

    // A position stays with its person and organisation.
    $this->withHeaders($headers)->patchJson(personPositionsUrl($member, $position), [
        'organization_id' => $organization->external_id,
    ])->assertStatus(422)->assertJsonValidationErrors(['organization_id']);

    expect(Position::count())->toBe(1);
    expect($position->fresh()->end_date)->toBeNull();
});

test('a position of another person or organisation is a 404 under the URL parent', function () {
    $headers = positionApiHeaders(positionApiUser());
    $organization = Organization::create(['name' => 'JumpSkipAreo']);
    $other = Organization::create(['name' => 'Other Co']);
    $casey = positionApiPerson('Casey', $organization);
    $gale = positionApiPerson('Gale', $organization);
    $position = positionApiPosition($casey, $organization, 'CFO');

    $this->withHeaders($headers)->getJson(personPositionsUrl($gale, $position))->assertStatus(404);
    $this->withHeaders($headers)->getJson(organizationPositionsUrl($other, $position))->assertStatus(404);
    $this->withHeaders($headers)->deleteJson(personPositionsUrl($gale, $position))->assertStatus(404);
    $this->withHeaders($headers)->getJson(organizationPositionsUrl($organization, $position))->assertOk();
});

test('position writes require edit crm people, reads require view crm people', function () {
    $organization = Organization::create(['name' => 'JumpSkipAreo']);
    $person = positionApiPerson('Casey', $organization);
    $position = positionApiPosition($person, $organization, 'CFO');

    $viewer = positionApiHeaders(positionApiUser(['crm_permissions' => json_encode(['view crm people', 'view crm organizations'])]));

    $this->withHeaders($viewer)->getJson(personPositionsUrl($person))->assertOk();
    $this->withHeaders($viewer)->getJson(organizationPositionsUrl($organization, $position))->assertOk();
    $this->withHeaders($viewer)->postJson(personPositionsUrl($person), ['title' => 'CEO'])->assertStatus(403);
    $this->withHeaders($viewer)->putJson(personPositionsUrl($person, $position), ['title' => 'CEO'])->assertStatus(403);
    $this->withHeaders($viewer)->deleteJson(personPositionsUrl($person, $position))->assertStatus(403);

    expect(Position::count())->toBe(1);
    expect($position->fresh()->title)->toBe('CFO');
});

test('position reads return 403 without view crm people', function () {
    $organization = Organization::create(['name' => 'JumpSkipAreo']);
    $person = positionApiPerson('Casey', $organization);
    $position = positionApiPosition($person, $organization, 'CFO');

    // Can open the organisation, but not people or their positions.
    $headers = positionApiHeaders(positionApiUser(['crm_permissions' => json_encode(['view crm organizations'])]));

    $this->withHeaders($headers)->getJson(personPositionsUrl($person))->assertStatus(403);
    $this->withHeaders($headers)->getJson(organizationPositionsUrl($organization))->assertStatus(403);
    $this->withHeaders($headers)->getJson(organizationPositionsUrl($organization, $position))->assertStatus(403);
});

test('the person resource carries current_positions', function () {
    $headers = positionApiHeaders(positionApiUser());
    $organization = Organization::create(['name' => 'JumpSkipAreo']);
    $person = positionApiPerson('Casey', $organization);
    positionApiPosition($person, $organization, 'Analyst', ['end_date' => '2026-02-01']);
    $cfo = positionApiPosition($person, $organization, 'CFO', ['start_date' => '2026-02-01']);

    $show = $this->withHeaders($headers)->getJson('/crm/api/v2/people/'.$person->external_id);
    $show->assertOk();
    expect($show->json('data.current_positions'))->toHaveCount(1);
    expect($show->json('data.current_positions.0.id'))->toBe($cfo->external_id);
    expect($show->json('data.current_positions.0.title'))->toBe('CFO');
    expect($show->json('data.current_positions.0.organization.name'))->toBe('JumpSkipAreo');

    $index = $this->withHeaders($headers)->getJson('/crm/api/v2/people');
    expect($index->json('data.0.current_positions.0.title'))->toBe('CFO');
});

test('moving a person to another organisation through the API ends their positions at the old one', function () {
    $headers = positionApiHeaders(positionApiUser());
    $organization = Organization::create(['name' => 'JumpSkipAreo']);
    $other = Organization::create(['name' => 'Other Co']);
    $person = positionApiPerson('Casey', $organization);
    $cfo = positionApiPosition($person, $organization, 'CFO');
    $board = positionApiPosition($person, $other, 'Board member');

    $this->withHeaders($headers)->putJson('/crm/api/v2/people/'.$person->external_id, [
        'first_name' => 'Casey',
        'organization_id' => $other->external_id,
    ])->assertOk();

    expect($cfo->fresh()->end_date->toDateString())->toBe(now()->toDateString());
    expect($board->fresh()->end_date)->toBeNull();
});

test('with the positions module disabled the endpoints are 403 and people carry no current_positions', function () {
    config()->set('laravel-crm.modules', ['leads', 'deals']);

    $headers = positionApiHeaders(positionApiUser(['crm_permissions' => json_encode([
        'view crm people', 'edit crm people', 'view crm organizations',
    ])]));
    $organization = Organization::create(['name' => 'JumpSkipAreo']);
    $other = Organization::create(['name' => 'Other Co']);
    $person = positionApiPerson('Casey', $organization);
    $position = positionApiPosition($person, $organization, 'CFO');

    $this->withHeaders($headers)->getJson(personPositionsUrl($person))->assertStatus(403);
    $this->withHeaders($headers)->getJson(organizationPositionsUrl($organization, $position))->assertStatus(403);
    $this->withHeaders($headers)->postJson(personPositionsUrl($person), ['title' => 'CEO'])->assertStatus(403);
    $this->withHeaders($headers)->putJson(personPositionsUrl($person, $position), ['title' => 'CEO'])->assertStatus(403);
    $this->withHeaders($headers)->deleteJson(personPositionsUrl($person, $position))->assertStatus(403);

    $show = $this->withHeaders($headers)->getJson('/crm/api/v2/people/'.$person->external_id);
    $show->assertOk();
    expect($show->json('data'))->not->toHaveKey('current_positions');

    // Positions are left alone while the module is off.
    $this->withHeaders($headers)->putJson('/crm/api/v2/people/'.$person->external_id, [
        'first_name' => 'Casey',
        'organization_id' => $other->external_id,
    ])->assertOk();

    expect(Position::count())->toBe(1);
    expect($position->fresh()->title)->toBe('CFO');
    expect($position->fresh()->end_date)->toBeNull();
});
