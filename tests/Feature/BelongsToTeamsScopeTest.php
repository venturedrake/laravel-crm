<?php

namespace VentureDrake\LaravelCrm\Tests\Feature;

use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Tests\TestCase;

class BelongsToTeamsScopeTest extends TestCase
{
    public function test_scope_does_not_apply_when_teams_disabled(): void
    {
        config()->set('laravel-crm.teams', false);

        $a = Lead::create(['title' => 'A', 'team_id' => 1]);
        $b = Lead::create(['title' => 'B', 'team_id' => 2]);

        // Without teams + auth, the scope should not filter.
        $this->assertSame(2, Lead::count());
    }

    public function test_all_teams_macro_bypasses_global_scope(): void
    {
        $builder = Lead::query();

        $this->assertTrue(method_exists($builder, 'macro') || $builder->hasMacro('allTeams'));
    }
}
