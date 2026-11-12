<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Position;
use VentureDrake\LaravelCrm\Services\PositionService;
use VentureDrake\LaravelCrm\Support\Modules;

/*
 * Positions is an optional module (`positions` in
 * `laravel-crm.modules`). Off hides it and denies every ability; the data stays.
 */

function positionsDirective(): string
{
    return Blade::render('@haspositionsenabled YES @else NO @endhaspositionsenabled');
}

it('is on in the default config', function () {
    expect(config('laravel-crm.modules'))->toContain('positions')
        ->and(Modules::enabled('positions'))->toBeTrue()
        ->and(trim(positionsDirective()))->toBe('YES');
});

it('is on when the modules value is empty or unset', function ($value) {
    config()->set('laravel-crm.modules', $value);

    expect(Modules::enabled('positions'))->toBeTrue()
        ->and(trim(positionsDirective()))->toBe('YES');
})->with([[null], [[]]]);

it('is off when the modules list does not contain it', function () {
    config()->set('laravel-crm.modules', ['leads', 'deals']);

    expect(Modules::enabled('positions'))->toBeFalse()
        ->and(trim(positionsDirective()))->toBe('NO');
});

it('grants position abilities from the people permissions', function () {
    $this->actingAsUserWithPermissions(['view crm people']);
    $position = new Position;

    expect(Gate::allows('viewAny', Position::class))->toBeTrue()
        ->and(Gate::allows('view', $position))->toBeTrue()
        ->and(Gate::allows('create', Position::class))->toBeFalse()
        ->and(Gate::allows('update', $position))->toBeFalse()
        ->and(Gate::allows('delete', $position))->toBeFalse();

    $this->actingAsUserWithPermissions(['view crm people', 'edit crm people']);

    expect(Gate::allows('create', Position::class))->toBeTrue()
        ->and(Gate::allows('update', $position))->toBeTrue()
        ->and(Gate::allows('delete', $position))->toBeTrue();
});

it('denies every position ability while the module is off', function () {
    config()->set('laravel-crm.modules', ['leads']);
    // An explicit list: the stub user with no list is granted everything
    // before a policy is consulted.
    $this->actingAsUserWithPermissions(['view crm people', 'edit crm people']);
    $position = new Position;

    expect(Gate::allows('viewAny', Position::class))->toBeFalse()
        ->and(Gate::allows('view', $position))->toBeFalse()
        ->and(Gate::allows('create', Position::class))->toBeFalse()
        ->and(Gate::allows('update', $position))->toBeFalse()
        ->and(Gate::allows('delete', $position))->toBeFalse();
});

it('keeps position records while off and shows them again when back on', function () {
    $this->actingAsUser(['crm_access' => 1]);
    $person = Person::create(['first_name' => 'Fran']);
    $org = Organization::create(['name' => 'JumpSkipAreo']);
    app(PositionService::class)->create(['person_id' => $person->id, 'organization_id' => $org->id, 'title' => 'CFO']);

    config()->set('laravel-crm.modules', ['leads']);
    expect($person->fresh()->current_position_title)->toBeNull()
        ->and(Position::count())->toBe(1);

    config()->set('laravel-crm.modules', ['leads', 'positions']);
    expect($person->fresh()->current_position_title)->toBe('CFO');
});

it('creates and drops the positions table from the loaded migration', function () {
    $files = glob(dirname(__DIR__, 2).'/database/updates/*_create_laravel_crm_positions_table.php');

    expect($files)->toHaveCount(1);

    // The test schema builds its own copy of the table; replace it with the
    // one the real migration creates.
    Schema::drop('crm_positions');
    $migration = require $files[0];
    $migration->up();

    expect(Schema::hasColumns('crm_positions', [
        'external_id', 'team_id', 'person_id', 'organization_id', 'title', 'start_date', 'end_date',
        'description', 'user_created_id', 'user_updated_id', 'user_deleted_id', 'user_restored_id', 'deleted_at',
    ]))->toBeTrue();

    // Re-running is a no-op, as laravelcrm:update expects.
    $migration->up();

    $migration->down();
    expect(Schema::hasTable('crm_positions'))->toBeFalse();
});
