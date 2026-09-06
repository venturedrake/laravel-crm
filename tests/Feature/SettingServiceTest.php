<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Tests\Stubs\CountingCacheStore;

test('set creates a new setting', function () {
    $setting = app('laravel-crm.settings')->set('lead_prefix', 'L', 'Lead Prefix');

    expect($setting)->toBeInstanceOf(Setting::class);
    $this->assertDatabaseHas('crm_settings', [
        'name' => 'lead_prefix',
        'value' => 'L',
        'label' => 'Lead Prefix',
    ]);
});

test('set updates an existing setting', function () {
    $service = app('laravel-crm.settings');
    $service->set('currency', 'USD');
    $service->set('currency', 'AUD');

    expect(Setting::where('name', 'currency')->count())->toBe(1);
    expect(Setting::where('name', 'currency')->first()->value)->toBe('AUD');
});

test('get returns default when setting missing', function () {
    $service = app('laravel-crm.settings');

    expect($service->get('does_not_exist', 'fallback'))->toBe('fallback');
    expect($service->get('does_not_exist'))->toBeNull();
});

test('all returns settings keyed by name', function () {
    $service = app('laravel-crm.settings');
    $service->set('a', '1');
    $service->set('b', '2');
    $service->forgetCache();

    $all = $service->all();

    expect($all['a'])->toBe('1');
    expect($all['b'])->toBe('2');
});

test('all is cached', function () {
    $service = app('laravel-crm.settings');
    $service->set('cached', 'first');
    $service->forgetCache();

    expect($service->get('cached'))->toBe('first');

    Setting::where('name', 'cached')->update(['value' => 'second']);

    // Cached value still returned
    expect($service->get('cached'))->toBe('first');

    $service->forgetCache();

    expect($service->get('cached'))->toBe('second');
});

test('first returns underlying model', function () {
    $service = app('laravel-crm.settings');
    $service->set('lookup', 'value');

    $found = $service->first('lookup');

    expect($found)->toBeInstanceOf(Setting::class);
    expect($found->value)->toBe('value');
});

test('forget cache removes cached entry', function () {
    $service = app('laravel-crm.settings');
    $service->set('x', 'y');
    $service->all();

    expect(Cache::has($service->cacheKey()))->toBeTrue();

    $service->forgetCache();

    expect(Cache::has($service->cacheKey()))->toBeFalse();
});

test('repeated reads within a request do not keep hitting the cache store', function () {
    // SettingsComposer is registered against every view, so a page render asks
    // for the map hundreds of times. Each one used to cost two store reads —
    // the generation counter and the map — which measured ~3000 redis calls on
    // a single dashboard render.
    Cache::extend('counting', fn () => Cache::repository(new CountingCacheStore));
    config([
        'cache.stores.counting' => ['driver' => 'counting'],
        'cache.default' => 'counting',
    ]);
    Cache::purge('counting');

    $service = app('laravel-crm.settings');
    $service->set('date_format', 'd/m/Y');

    CountingCacheStore::reset();

    for ($i = 0; $i < 50; $i++) {
        expect($service->get('date_format'))->toBe('d/m/Y');
    }

    // One for the generation counter, one for the map, and nothing after that.
    expect(CountingCacheStore::$reads)->toBeLessThanOrEqual(3);
});

test('a write is visible to the rest of the request that made it', function () {
    $service = app('laravel-crm.settings');
    $service->set('date_format', 'd/m/Y');

    // Fill the memo before the second write, so a stale one would be caught.
    expect($service->get('date_format'))->toBe('d/m/Y');

    $service->set('date_format', 'm/d/Y');

    expect($service->get('date_format'))->toBe('m/d/Y');
});

test('all excludes user scoped rows', function () {
    $service = app('laravel-crm.settings');
    $service->set('global_only', 'global');
    $service->setForUser(1, 'user_only', 'mine');
    $service->forgetCache();

    $all = $service->all();

    expect($all)->toHaveKey('global_only')
        ->and($all)->not->toHaveKey('user_only');
});

test('a user scoped row does not shadow the global value of the same name', function () {
    $service = app('laravel-crm.settings');

    // Global row first, then a user row of the same name. pluck() keys by name,
    // so without the whereNull the user row would overwrite the global one.
    $service->set('date_format', 'd/m/Y');
    $service->setForUser(1, 'date_format', 'm/d/Y');
    $service->forgetCache();

    expect($service->all()['date_format'])->toBe('d/m/Y')
        ->and($service->get('date_format'))->toBe('d/m/Y');
});

test('get for user reads back the value written by set for user', function () {
    $service = app('laravel-crm.settings');

    $service->setForUser(7, 'system_check_dismissed', 'abc123');

    expect($service->getForUser(7, 'system_check_dismissed'))->toBe('abc123');
});

test('set for user upserts on user id plus name', function () {
    $service = app('laravel-crm.settings');

    $service->setForUser(7, 'system_check_dismissed', 'first');
    $service->setForUser(7, 'system_check_dismissed', 'second');

    expect(Setting::where('user_id', 7)->where('name', 'system_check_dismissed')->count())->toBe(1)
        ->and($service->getForUser(7, 'system_check_dismissed'))->toBe('second');
});

test('set for user keeps different users independent', function () {
    $service = app('laravel-crm.settings');

    $service->setForUser(1, 'system_check_dismissed', 'user-one');
    $service->setForUser(2, 'system_check_dismissed', 'user-two');

    expect(Setting::where('name', 'system_check_dismissed')->count())->toBe(2)
        ->and($service->getForUser(1, 'system_check_dismissed'))->toBe('user-one')
        ->and($service->getForUser(2, 'system_check_dismissed'))->toBe('user-two');
});

test('get for user returns the default when no row exists', function () {
    $service = app('laravel-crm.settings');

    expect($service->getForUser(99, 'never_set', 'fallback'))->toBe('fallback')
        ->and($service->getForUser(99, 'never_set'))->toBeNull();
});

test('get for user does not fall back to the global row', function () {
    $service = app('laravel-crm.settings');
    $service->set('date_format', 'd/m/Y');

    expect($service->getForUser(1, 'date_format', 'fallback'))->toBe('fallback');
});

test('set for user does not collide with the global setter', function () {
    $service = app('laravel-crm.settings');

    $service->set('date_format', 'd/m/Y');
    $service->setForUser(1, 'date_format', 'm/d/Y');

    expect(Setting::whereNull('user_id')->where('name', 'date_format')->count())->toBe(1)
        ->and(Setting::where('user_id', 1)->where('name', 'date_format')->count())->toBe(1)
        ->and($service->first('date_format')->value)->toBe('d/m/Y');
});

test('all still works on a host that never ran the add_user migration', function () {
    $service = app('laravel-crm.settings');
    $service->set('date_format', 'd/m/Y');
    $service->forgetCache();

    $table = (new Setting)->getTable();

    Schema::table($table, fn (Blueprint $t) => $t->dropColumn('user_id'));

    try {
        expect(Schema::hasColumn($table, 'user_id'))->toBeFalse()
            ->and($service->all()['date_format'])->toBe('d/m/Y');
    } finally {
        Schema::table($table, fn (Blueprint $t) => $t->unsignedBigInteger('user_id')->nullable());
    }
});

test('per-user reads and writes degrade instead of throwing without the add_user migration', function () {
    // The banner reads a per-user dismissal on the way to reporting a
    // behind-schema install — which is exactly the install most likely to be
    // missing this column. It has to degrade, not fatal.
    $service = app('laravel-crm.settings');
    $table = (new Setting)->getTable();

    Schema::table($table, fn (Blueprint $t) => $t->dropColumn('user_id'));

    try {
        expect($service->getForUser(1, 'system_check_dismissed', 'fallback'))->toBe('fallback')
            ->and($service->setForUser(1, 'system_check_dismissed', 'abc'))->toBeNull()
            ->and(Setting::where('name', 'system_check_dismissed')->count())->toBe(0);
    } finally {
        Schema::table($table, fn (Blueprint $t) => $t->unsignedBigInteger('user_id')->nullable());
    }
});

test('set install wide creates a global row when none exists', function () {
    $setting = app('laravel-crm.settings')->setInstallWide('db_update_1201', 1);

    expect((int) $setting->global)->toBe(1)
        ->and((int) $setting->value)->toBe(1)
        ->and(Setting::where('name', 'db_update_1201')->count())->toBe(1);
});

/**
 * forTeam() is what makes the anonymous portal read the right tenant's
 * branding: BelongsToTeamsScope only engages for a signed-in user with a
 * current team, so without an explicit override a portal request reads every
 * team's rows and pluck() keeps whichever one the database listed last.
 */
function settingRowFor(?int $teamId, string $name, string $value, int $global = 0): void
{
    Setting::withoutGlobalScopes()->create([
        'name' => $name,
        'value' => $value,
        'team_id' => $teamId,
        'global' => $global,
    ]);
}

test('for team reads that teams rows and not another teams', function () {
    config()->set('laravel-crm.teams', true);

    settingRowFor(1, 'organization_name', 'A Co');
    settingRowFor(2, 'organization_name', 'B Co');

    $service = app('laravel-crm.settings');

    expect($service->forTeam(2)->get('organization_name'))->toBe('B Co')
        ->and($service->forTeam(1)->get('organization_name'))->toBe('A Co');
});

test('for team with no team reads neither teams rows', function () {
    // A document written before teams existed. Rendering a blank From block is
    // correct; borrowing whichever tenant sorted last is the bug.
    config()->set('laravel-crm.teams', true);

    settingRowFor(1, 'organization_name', 'A Co');
    settingRowFor(2, 'organization_name', 'B Co');

    expect(app('laravel-crm.settings')->forTeam(null)->get('organization_name'))->toBeNull();
});

test('for team is inert when teams are disabled', function () {
    config()->set('laravel-crm.teams', false);

    settingRowFor(null, 'organization_name', 'Single Tenant Co');

    expect(app('laravel-crm.settings')->forTeam(2)->get('organization_name'))->toBe('Single Tenant Co');
});

test('two for team values in one request do not share a cache entry', function () {
    config()->set('laravel-crm.teams', true);

    $service = app('laravel-crm.settings');

    $keyForTeamOne = $service->forTeam(1)->cacheKey();
    $keyForTeamTwo = $service->forTeam(2)->cacheKey();
    $keyForNoTeam = $service->forTeam(null)->cacheKey();

    expect($keyForTeamOne)->toContain('.team.1')
        ->and($keyForTeamTwo)->toContain('.team.2')
        ->and($keyForNoTeam)->toContain('.team.none')
        ->and([$keyForTeamOne, $keyForTeamTwo, $keyForNoTeam])->toHaveCount(3)
        ->and(array_unique([$keyForTeamOne, $keyForTeamTwo, $keyForNoTeam]))->toHaveCount(3);
});

test('for team pins the key even where the team scope stands down', function () {
    // The whole point: on an anonymous request the scope is inert, so the
    // unsuffixed key would be filled with an all-teams map and handed to every
    // console command and queued job that reads settings.
    config()->set('laravel-crm.teams', true);

    $service = app('laravel-crm.settings');
    $unpinned = $service->cacheKey();

    expect($service->forTeam(2)->cacheKey())->not->toBe($unpinned);
});

test('get for user is a direct query rather than a cache read', function () {
    $service = app('laravel-crm.settings');
    $service->setForUser(1, 'live', 'before');

    // Warm the global cache; per-user reads must not be served from it.
    $service->all();

    Setting::where('user_id', 1)->where('name', 'live')->update(['value' => 'after']);

    expect($service->getForUser(1, 'live'))->toBe('after');
});
