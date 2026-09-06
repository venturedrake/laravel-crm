<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Tests\Stubs\User;
use VentureDrake\LaravelCrm\View\Composers\SettingsComposer;

/**
 * The settings map is read through a cache. That cache sits in front of a
 * team-scoped query, so it has to be partitioned by team — otherwise whichever
 * enterprise warms it serves its organisation name, ABN and logo to every other
 * enterprise until the next settings write, both on the settings screen and on
 * the invoices those settings render.
 */
function settingsTeamUser(int $currentTeamId): User
{
    return User::create([
        'name' => 'Team User',
        'email' => 'settings-team-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
        'current_team_id' => $currentTeamId,
        'team_ids' => json_encode([$currentTeamId]),
    ]);
}

function switchSettingsTeam(User $user, int $teamId): void
{
    $user->unsetRelation('currentTeam');
    $user->forceFill(['current_team_id' => $teamId])->save();
}

beforeEach(function () {
    config()->set('laravel-crm.teams', true);
});

afterEach(function () {
    config()->set('laravel-crm.teams', false);
});

test('one team cannot read another teams settings out of the cache', function () {
    $user = settingsTeamUser(1);
    $this->actingAs($user);

    $service = app('laravel-crm.settings');
    $service->set('organization_name', 'A Co');

    // Warm the cache as team 1.
    expect($service->get('organization_name'))->toBe('A Co');

    switchSettingsTeam($user, 2);

    expect($service->get('organization_name'))->not->toBe('A Co')
        ->and($service->get('organization_name'))->toBeNull()
        ->and($service->all())->not->toHaveKey('organization_name');
});

test('each team reads back its own settings', function () {
    $user = settingsTeamUser(1);
    $this->actingAs($user);

    $service = app('laravel-crm.settings');
    $service->set('organization_name', 'A Co');

    switchSettingsTeam($user, 2);
    $service->set('organization_name', 'B Co');

    expect($service->get('organization_name'))->toBe('B Co');

    switchSettingsTeam($user, 1);

    expect($service->get('organization_name'))->toBe('A Co')
        ->and(Setting::withoutGlobalScopes()->where('name', 'organization_name')->count())->toBe(2);
});

test('the settings cache key is partitioned by team', function () {
    $user = settingsTeamUser(1);
    $this->actingAs($user);

    $service = app('laravel-crm.settings');
    $keyForTeamOne = $service->cacheKey();

    switchSettingsTeam($user, 2);

    expect($service->cacheKey())->not->toBe($keyForTeamOne);
});

test('the cache key carries no team on nova requests', function () {
    // BelongsToTeamsScope stands down under Nova, so all() there returns every
    // team's rows. Filing that map under one team's key would hand that team
    // another tenant's organisation name for the rest of the TTL.
    config()->set('nova.path', '/nova');

    $this->actingAs(settingsTeamUser(1));

    $service = app('laravel-crm.settings');

    expect($service->cacheKey())->toContain('.team.1');

    app()->instance('request', Request::create('/nova/dashboard'));

    expect($service->cacheKey())->not->toContain('.team.');
});

test('the cache key carries no team when teams are disabled', function () {
    config()->set('laravel-crm.teams', false);

    $this->actingAs(settingsTeamUser(1));

    expect(app('laravel-crm.settings')->cacheKey())->not->toContain('.team.');
});

test('the view composer serves each team its own formats', function () {
    $user = settingsTeamUser(1);
    $this->actingAs($user);

    app('laravel-crm.settings')->set('tax_name', 'GST');
    app('laravel-crm.settings')->set('date_format', 'd/m/Y');

    $compose = function () {
        $view = view('laravel-crm::leads.index');
        (new SettingsComposer)->compose($view);

        return $view->getData();
    };

    $teamOne = $compose();

    expect($teamOne['crmTaxName'])->toBe('GST')
        ->and($teamOne['crmDateFormat'])->toBe('d/m/Y');

    switchSettingsTeam($user, 2);

    $teamTwo = $compose();

    expect($teamTwo['crmTaxName'])->toBe('Tax')
        ->and($teamTwo['crmDateFormat'])->toBe('Y-m-d');
});

test('a write by one team invalidates every teams cached map', function () {
    // Invalidation deliberately spans teams: rows flagged global appear in more
    // than one team's map, so a write to any of them has to drop all of them.
    $user = settingsTeamUser(1);
    $this->actingAs($user);

    $service = app('laravel-crm.settings');
    $service->set('organization_name', 'A Co');

    // Warm team 1's map.
    expect($service->get('organization_name'))->toBe('A Co');

    // Change team 1's row behind the observer's back, so only a refetch can see it.
    DB::table((new Setting)->getTable())
        ->where('name', 'organization_name')
        ->update(['value' => 'A Co Renamed']);

    expect($service->get('organization_name'))->toBe('A Co', 'the map should still be cached');

    // Team 2 writes an unrelated setting.
    switchSettingsTeam($user, 2);
    $service->set('unrelated', 'anything');
    switchSettingsTeam($user, 1);

    expect($service->get('organization_name'))->toBe('A Co Renamed');
});
