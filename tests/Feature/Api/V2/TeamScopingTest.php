<?php

use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\LeadStatus;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function teamScopingUser(array $teamIds, ?int $currentTeamId = null): User
{
    return User::create([
        'name' => 'Team User',
        'email' => 'team-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
        'current_team_id' => $currentTeamId ?? ($teamIds[0] ?? null),
        'team_ids' => json_encode($teamIds),
    ]);
}

function teamScopingHeaders(User $user, ?int $teamId = null): array
{
    $token = $user->createToken('team-scoping-test')->plainTextToken;

    $headers = [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ];

    if ($teamId !== null) {
        $headers['X-Team-ID'] = (string) $teamId;
    }

    return $headers;
}

function seedTeamLeadStatus(): void
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
    config()->set('laravel-crm.teams', true);
    seedTeamLeadStatus();
});

afterEach(function () {
    config()->set('laravel-crm.teams', false);
});

/*
 * Scenario (a): user A's token cannot list or show entities owned by team B.
 */
test('GET /leads filters list to the active team only (no cross-team leakage)', function () {
    $userA = teamScopingUser([1], currentTeamId: 1);

    Lead::create(['title' => 'Team A Lead 1', 'team_id' => 1]);
    Lead::create(['title' => 'Team A Lead 2', 'team_id' => 1]);
    Lead::create(['title' => 'Team B Lead', 'team_id' => 2]);

    $response = $this->withHeaders(teamScopingHeaders($userA))
        ->getJson('/api/crm/v2/leads');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);

    $titles = collect($response->json('data'))->pluck('title')->all();
    expect($titles)->toContain('Team A Lead 1');
    expect($titles)->toContain('Team A Lead 2');
    expect($titles)->not->toContain('Team B Lead');
});

test('GET /leads/{lead} returns 404 for an entity owned by another team', function () {
    $userA = teamScopingUser([1], currentTeamId: 1);

    $teamBLead = Lead::create(['title' => 'Hidden', 'team_id' => 2]);

    $response = $this->withHeaders(teamScopingHeaders($userA))
        ->getJson('/api/crm/v2/leads/'.$teamBLead->external_id);

    $response->assertStatus(404);
});

test('cross-team isolation holds for organizations and people endpoints', function () {
    $userA = teamScopingUser([1], currentTeamId: 1);

    Organization::create(['name' => 'Team A Org', 'team_id' => 1]);
    Organization::create(['name' => 'Team B Org', 'team_id' => 2]);

    Person::create(['first_name' => 'Alice', 'team_id' => 1]);
    Person::create(['first_name' => 'Bob', 'team_id' => 2]);

    $orgResponse = $this->withHeaders(teamScopingHeaders($userA))
        ->getJson('/api/crm/v2/organizations');
    $orgResponse->assertOk();
    expect($orgResponse->json('meta.total'))->toBe(1);
    expect($orgResponse->json('data.0.name'))->toBe('Team A Org');

    $peopleResponse = $this->withHeaders(teamScopingHeaders($userA))
        ->getJson('/api/crm/v2/people');
    $peopleResponse->assertOk();
    expect($peopleResponse->json('meta.total'))->toBe(1);
    expect($peopleResponse->json('data.0.first_name'))->toBe('Alice');
});

/*
 * Scenario (b): X-Team-ID for a team the user does NOT belong to returns 403.
 */
test('X-Team-ID for a non-member team returns 403 with a JSON error', function () {
    $userA = teamScopingUser([1], currentTeamId: 1);

    Lead::create(['title' => 'Team B Lead', 'team_id' => 2]);

    $response = $this->withHeaders(teamScopingHeaders($userA, teamId: 2))
        ->getJson('/api/crm/v2/leads');

    $response->assertStatus(403);
    $response->assertJsonStructure(['message']);
    expect($response->json('message'))->toBe('You are not a member of the requested team.');
});

/*
 * Scenario (c): X-Team-ID for a team the user DOES belong to scopes lists correctly.
 */
test('X-Team-ID for a member team scopes the list to that team', function () {
    $userA = teamScopingUser([1, 2], currentTeamId: 1);

    Lead::create(['title' => 'Team A Lead', 'team_id' => 1]);
    Lead::create(['title' => 'Team B Lead 1', 'team_id' => 2]);
    Lead::create(['title' => 'Team B Lead 2', 'team_id' => 2]);

    $response = $this->withHeaders(teamScopingHeaders($userA, teamId: 2))
        ->getJson('/api/crm/v2/leads');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);

    $titles = collect($response->json('data'))->pluck('title')->all();
    expect($titles)->toContain('Team B Lead 1');
    expect($titles)->toContain('Team B Lead 2');
    expect($titles)->not->toContain('Team A Lead');
});

/*
 * Scenario (d): absence of X-Team-ID falls back to the user's current_team_id.
 */
test('Without X-Team-ID the request falls back to current_team_id', function () {
    $userA = teamScopingUser([1, 2], currentTeamId: 2);

    Lead::create(['title' => 'Team 1 Lead', 'team_id' => 1]);
    Lead::create(['title' => 'Team 2 Lead A', 'team_id' => 2]);
    Lead::create(['title' => 'Team 2 Lead B', 'team_id' => 2]);

    $response = $this->withHeaders(teamScopingHeaders($userA))
        ->getJson('/api/crm/v2/leads');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);

    $titles = collect($response->json('data'))->pluck('title')->all();
    expect($titles)->toContain('Team 2 Lead A');
    expect($titles)->toContain('Team 2 Lead B');
    expect($titles)->not->toContain('Team 1 Lead');
});
