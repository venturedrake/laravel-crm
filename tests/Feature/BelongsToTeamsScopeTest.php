<?php

use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Scopes\BelongsToTeamsScope;

test('scope does not apply when teams disabled', function () {
    config()->set('laravel-crm.teams', false);

    Lead::create(['title' => 'A', 'team_id' => 1]);
    Lead::create(['title' => 'B', 'team_id' => 2]);

    expect(Lead::count())->toBe(2);
});

test('belongs to teams scope method exists on model', function () {
    expect(method_exists(Lead::class, 'bootBelongsToTeams'))->toBeTrue();
});

test('belongs to teams global scope class is registered', function () {
    $scopes = Lead::query()->getModel()->getGlobalScopes();

    expect($scopes)->toHaveKey(BelongsToTeamsScope::class);
});
