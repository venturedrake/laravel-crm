<?php

use Illuminate\Support\Facades\DB;
use VentureDrake\LaravelCrm\Models\Team;
use VentureDrake\LaravelCrm\Tests\Stubs\NonJetstreamUser;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

/**
 * Jetstream's own switchTeam() refuses a team the user does not belong to. The
 * fallback branch — hosts that carry a current_team_id column without Jetstream
 * — has to make the same check itself, or any authenticated user can PUT any
 * team id and land inside that tenant's data.
 */
function currentTeamUser(array $teamIds, int $currentTeamId): User
{
    return User::create([
        'name' => 'Switching User',
        'email' => 'switch-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
        'current_team_id' => $currentTeamId,
        'team_ids' => json_encode($teamIds),
    ]);
}

/**
 * The realistic shape of the fallback branch: no allTeams(), so membership can
 * only come from crm_team_user.
 */
function nonJetstreamUser(array $crmTeamIds, int $currentTeamId): NonJetstreamUser
{
    $user = NonJetstreamUser::create([
        'name' => 'Plain User',
        'email' => 'plain-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
        'current_team_id' => $currentTeamId,
    ]);

    $user->crmTeams()->sync($crmTeamIds);

    return $user;
}

/**
 * A crm_teams row pinned to an explicit grouping.
 *
 * BelongsToTeams normally stamps crm_teams.team_id with whatever team the
 * creator was in, so the column decides which teams a given caller can see at
 * all. These rows are created with teams switched off, so the grouping has to
 * be written directly.
 */
function groupedCrmTeam(string $name, int $grouping, int $ownerId = 999): Team
{
    $team = Team::withoutGlobalScopes()->create(['name' => $name, 'user_id' => $ownerId]);

    DB::table('crm_teams')->where('id', $team->id)->update(['team_id' => $grouping]);

    return $team;
}

beforeEach(function () {
    $this->teamOne = Team::create(['name' => 'Team One', 'user_id' => 1]);
    $this->teamTwo = Team::create(['name' => 'Team Two', 'user_id' => 2]);
});

test('switching into a team the user does not belong to is forbidden', function () {
    $user = currentTeamUser([$this->teamOne->id], $this->teamOne->id);

    $this->actingAs($user)
        ->put(route('current-team.update'), ['team_id' => $this->teamTwo->id])
        ->assertForbidden();

    expect($user->fresh()->current_team_id)->toBe($this->teamOne->id);
});

test('switching into a team the user belongs to is allowed', function () {
    $user = currentTeamUser([$this->teamOne->id, $this->teamTwo->id], $this->teamOne->id);

    $this->actingAs($user)
        ->put(route('current-team.update'), ['team_id' => $this->teamTwo->id])
        ->assertRedirect();

    expect($user->fresh()->current_team_id)->toBe($this->teamTwo->id);
});

test('a user without allTeams cannot switch into a crm team they are not in', function () {
    $user = nonJetstreamUser([$this->teamOne->id], $this->teamOne->id);

    $this->actingAs($user)
        ->put(route('current-team.update'), ['team_id' => $this->teamTwo->id])
        ->assertForbidden();

    expect($user->fresh()->current_team_id)->toBe($this->teamOne->id);
});

test('a user without allTeams can switch into a crm team they are in', function () {
    $user = nonJetstreamUser([$this->teamOne->id, $this->teamTwo->id], $this->teamOne->id);

    $this->actingAs($user)
        ->put(route('current-team.update'), ['team_id' => $this->teamTwo->id])
        ->assertRedirect();

    expect($user->fresh()->current_team_id)->toBe($this->teamTwo->id);
});

test('owning a crm team is enough to switch into it', function () {
    $user = nonJetstreamUser([$this->teamOne->id], $this->teamOne->id);
    $this->teamTwo->forceFill(['user_id' => $user->id])->save();

    $this->actingAs($user)
        ->put(route('current-team.update'), ['team_id' => $this->teamTwo->id])
        ->assertRedirect();

    expect($user->fresh()->current_team_id)->toBe($this->teamTwo->id);
});

test('a host that records no crm memberships is not locked out', function () {
    // Nothing in crm_team_user for this user, so membership is unknowable
    // rather than absent — blocking here would break hosts that only ever set
    // current_team_id.
    $user = nonJetstreamUser([], $this->teamOne->id);

    $this->actingAs($user)
        ->put(route('current-team.update'), ['team_id' => $this->teamTwo->id])
        ->assertRedirect();

    expect($user->fresh()->current_team_id)->toBe($this->teamTwo->id);
});

/**
 * With teams enabled the BelongsToTeams scope is live, and that is the only
 * configuration in which team switching means anything. Everything above runs
 * with teams off, where the scope is inert — so the membership check has to be
 * exercised again here.
 */
describe('with teams enabled', function () {
    beforeEach(function () {
        config()->set('laravel-crm.teams', true);

        // Both reachable from the home grouping, so the controller's scoped
        // findOrFail() can see the target and the membership check is what
        // decides the outcome.
        $this->home = groupedCrmTeam('Home', 1);
        DB::table('crm_teams')->where('id', $this->home->id)->update(['team_id' => $this->home->id]);
        $this->target = groupedCrmTeam('Target', $this->home->id);
    });

    afterEach(function () {
        config()->set('laravel-crm.teams', false);
    });

    test('a recorded member of the target may switch into it', function () {
        $user = nonJetstreamUser([$this->home->id, $this->target->id], $this->home->id);

        $this->actingAs($user)
            ->put(route('current-team.update'), ['team_id' => $this->target->id])
            ->assertRedirect();

        expect($user->fresh()->current_team_id)->toBe($this->target->id);
    });

    test('a non-member is refused', function () {
        $user = nonJetstreamUser([$this->home->id], $this->home->id);

        $this->actingAs($user)
            ->put(route('current-team.update'), ['team_id' => $this->target->id])
            ->assertForbidden();

        expect($user->fresh()->current_team_id)->toBe($this->home->id);
    });

    test('a non-member is refused even when their memberships sit in another grouping', function () {
        // The membership probe runs through Team, which is itself team-scoped.
        // Read with that scope on, this user's rows are invisible and they look
        // like a host that records no memberships — the branch that waves
        // callers through. The grouping is the value being switched away from,
        // so it must not be what decides whether the check runs.
        $elsewhere = groupedCrmTeam('Elsewhere', 4242);

        $user = nonJetstreamUser([$elsewhere->id], $this->home->id);

        $this->actingAs($user)
            ->put(route('current-team.update'), ['team_id' => $this->target->id])
            ->assertForbidden();

        expect($user->fresh()->current_team_id)->toBe($this->home->id);
    });

    test('a host that records no memberships at all is still not locked out', function () {
        $user = nonJetstreamUser([], $this->home->id);

        $this->actingAs($user)
            ->put(route('current-team.update'), ['team_id' => $this->target->id])
            ->assertRedirect();

        expect($user->fresh()->current_team_id)->toBe($this->target->id);
    });

    test('the owner of the target may switch into it', function () {
        $user = nonJetstreamUser([$this->home->id], $this->home->id);

        DB::table('crm_teams')->where('id', $this->target->id)->update(['user_id' => $user->id]);

        $this->actingAs($user)
            ->put(route('current-team.update'), ['team_id' => $this->target->id])
            ->assertRedirect();

        expect($user->fresh()->current_team_id)->toBe($this->target->id);
    });
});

test('switching teams drops the cached settings map', function () {
    $user = currentTeamUser([$this->teamOne->id, $this->teamTwo->id], $this->teamOne->id);

    app('laravel-crm.settings')->set('organization_name', 'A Co');
    app('laravel-crm.settings')->all();

    $keyBefore = app('laravel-crm.settings')->cacheKey();

    expect(cache()->has($keyBefore))->toBeTrue();

    $this->actingAs($user)
        ->put(route('current-team.update'), ['team_id' => $this->teamTwo->id])
        ->assertRedirect();

    expect(cache()->has($keyBefore))->toBeFalse();
});
