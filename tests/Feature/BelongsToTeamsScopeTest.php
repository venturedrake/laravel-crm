<?php

namespace VentureDrake\LaravelCrm\Tests\Feature;

use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Scopes\BelongsToTeamsScope;
use VentureDrake\LaravelCrm\Tests\TestCase;

class BelongsToTeamsScopeTest extends TestCase
{
    public function test_scope_does_not_apply_when_teams_disabled(): void
    {
        config()->set('laravel-crm.teams', false);

        Lead::create(['title' => 'A', 'team_id' => 1]);
        Lead::create(['title' => 'B', 'team_id' => 2]);

        // Without teams + auth, the scope should not filter.
        $this->assertSame(2, Lead::count());
    }

    public function test_belongs_to_teams_scope_method_exists_on_model(): void
    {
        // The BelongsToTeams trait registers a global scope via bootBelongsToTeams().
        // The scope class is applied per-query; we assert the boot method exists
        // which is what Laravel hooks into automatically.
        $this->assertTrue(method_exists(Lead::class, 'bootBelongsToTeams'));
    }

    public function test_belongs_to_teams_global_scope_class_is_registered(): void
    {
        $scopes = Lead::query()->getModel()->getGlobalScopes();

        $this->assertArrayHasKey(
            BelongsToTeamsScope::class,
            $scopes
        );
    }
}
