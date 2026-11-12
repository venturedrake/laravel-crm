<?php

use Illuminate\Support\Carbon;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\People\PersonIndex;
use VentureDrake\LaravelCrm\Livewire\RelatedPositions;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Position;
use VentureDrake\LaravelCrm\Services\PositionService;

/*
 * The Positions panel on person and organisation pages, and position
 * search on the people index. The panel is rendered for real here, so a broken
 * view fails these tests too.
 */

beforeEach(function () {
    Carbon::setTestNow('2026-11-10 09:00:00');

    $this->actingAsUser(['crm_access' => 1]);
    $this->org = Organization::create(['name' => 'JumpSkipAreo']);
    $this->fran = Person::create(['first_name' => 'Fran', 'last_name' => 'Ops', 'organization_id' => $this->org->id]);
    $this->casey = Person::create(['first_name' => 'Casey', 'last_name' => 'Eo']);
});

afterEach(fn () => Carbon::setTestNow());

function hold(Person $person, Organization $org, string $title, array $extra = []): Position
{
    return app(PositionService::class)->create(array_merge([
        'person_id' => $person->id,
        'organization_id' => $org->id,
        'title' => $title,
    ], $extra), []);
}

// ---------------------------------------------------------------------
// Adding
// ---------------------------------------------------------------------

it('opens the form on a person page with today and the person\'s organisation', function () {
    Livewire::test(RelatedPositions::class, ['model' => $this->fran])
        ->call('add')
        ->assertSet('showPositionForm', true)
        ->assertSet('start_date', '2026-11-10')
        ->assertSet('organization_id', $this->org->id)
        ->assertSet('organization_name', 'JumpSkipAreo')
        ->assertSet('person_id', $this->fran->id);
});

it('adds a position from a person page', function () {
    Livewire::test(RelatedPositions::class, ['model' => $this->fran])
        ->call('add')
        ->set('title', 'CFO')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showPositionForm', false)
        ->assertSee('CFO')
        ->assertSee('JumpSkipAreo');

    $position = Position::sole();

    expect($position->person_id)->toBe($this->fran->id)
        ->and($position->organization_id)->toBe($this->org->id)
        ->and($position->start_date->toDateString())->toBe('2026-11-10')
        ->and($position->end_date)->toBeNull();
});

it('adds a position at another organisation without changing the main one', function () {
    $other = Organization::create(['name' => 'Other Co']);

    Livewire::test(RelatedPositions::class, ['model' => $this->fran])
        ->call('add')
        ->call('linkOrganization', $other->id)
        ->set('title', 'Director')
        ->call('save')
        ->assertHasNoErrors();

    expect(Position::sole()->organization_id)->toBe($other->id)
        ->and($this->fran->fresh()->organization_id)->toBe($this->org->id);
});

it('adds a position for an existing person from an organisation page', function () {
    Livewire::test(RelatedPositions::class, ['model' => $this->org])
        ->call('add')
        ->assertSet('organization_id', $this->org->id)
        ->call('linkPerson', $this->casey->id)
        ->set('title', 'CEO')
        ->set('start_date', '2025-02-01')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('CEO')
        ->assertSee('Casey Eo');

    expect(Position::sole()->only(['person_id', 'organization_id', 'title']))
        ->toBe(['person_id' => $this->casey->id, 'organization_id' => $this->org->id, 'title' => 'CEO'])
        ->and(Position::sole()->start_date->toDateString())->toBe('2025-02-01');
});

it('creates the person when a new name is typed on an organisation page', function () {
    Livewire::test(RelatedPositions::class, ['model' => $this->org])
        ->call('add')
        ->set('person_name', 'Gia Em')
        ->set('title', 'GM')
        ->call('save')
        ->assertHasNoErrors();

    $person = Person::where('first_name', 'Gia')->sole();

    expect($person->last_name)->toBe('Em')
        ->and($person->organization_id)->toBe($this->org->id)
        ->and(Position::sole()->person_id)->toBe($person->id);
});

it('requires a title, a person and a valid date range', function () {
    Livewire::test(RelatedPositions::class, ['model' => $this->org])
        ->call('add')
        ->set('start_date', '2026-05-01')
        ->set('end_date', '2026-04-01')
        ->call('save')
        ->assertHasErrors(['title', 'person_name', 'end_date']);

    expect(Position::count())->toBe(0);
});

// ---------------------------------------------------------------------
// Ending what a new position replaces
// ---------------------------------------------------------------------

it('lists the positions a new one will end and ends them by default', function () {
    $controller = hold($this->fran, $this->org, 'Financial Controller', ['start_date' => '2024-01-01']);
    $cfo = hold($this->casey, $this->org, 'CFO', ['start_date' => '2023-01-01']);

    Livewire::test(RelatedPositions::class, ['model' => $this->fran])
        ->call('add')
        ->set('title', 'cfo')
        ->assertSee(ucfirst(__('laravel-crm::lang.positions_to_end')))
        ->assertSee('Financial Controller')
        ->assertSee('Casey Eo')
        ->call('save')
        ->assertHasNoErrors();

    expect($controller->fresh()->end_date->toDateString())->toBe('2026-11-10')
        ->and($cfo->fresh()->end_date->toDateString())->toBe('2026-11-10')
        ->and($this->org->currentPositions()->sole()->person_id)->toBe($this->fran->id);
});

it('keeps open a position the user unticked', function () {
    $gm = hold($this->casey, $this->org, 'GM');

    Livewire::test(RelatedPositions::class, ['model' => $this->fran])
        ->call('add')
        ->set('title', 'GM')
        ->call('toggleKeepOpen', $gm->id)
        ->assertSet('keepOpen', [$gm->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($gm->fresh()->end_date)->toBeNull()
        ->and($this->org->currentPositions()->count())->toBe(2);
});

it('shows no list when nothing would be ended', function () {
    hold($this->casey, $this->org, 'CEO');

    Livewire::test(RelatedPositions::class, ['model' => $this->fran])
        ->call('add')
        ->set('title', 'CFO')
        ->assertDontSee(ucfirst(__('laravel-crm::lang.positions_to_end')));
});

// ---------------------------------------------------------------------
// Listing, editing, ending, deleting
// ---------------------------------------------------------------------

it('lists current positions and shows past ones on request', function () {
    hold($this->casey, $this->org, 'CEO');
    hold($this->fran, $this->org, 'Treasurer', ['start_date' => '2020-01-01', 'end_date' => '2022-01-01']);

    Livewire::test(RelatedPositions::class, ['model' => $this->org])
        ->assertSee('CEO')
        ->assertDontSee('Treasurer')
        ->set('showPast', true)
        ->assertSee('Treasurer');
});

it('narrows an organisation\'s positions by title or person', function () {
    hold($this->casey, $this->org, 'CEO');
    hold($this->fran, $this->org, 'CFO');

    Livewire::test(RelatedPositions::class, ['model' => $this->org])
        ->set('search', 'cfo')
        ->assertSee('Fran Ops')
        ->assertDontSee('Casey Eo')
        ->set('search', 'casey')
        ->assertSee('CEO')
        ->assertDontSee('Fran Ops');
});

it('edits a position', function () {
    $position = hold($this->fran, $this->org, 'CFO');

    Livewire::test(RelatedPositions::class, ['model' => $this->fran])
        ->call('edit', $position->id)
        ->assertSet('title', 'CFO')
        ->set('title', 'Chief Financial Officer')
        ->set('end_date', '2026-12-31')
        ->call('save')
        ->assertHasNoErrors();

    expect($position->fresh()->title)->toBe('Chief Financial Officer')
        ->and($position->fresh()->end_date->toDateString())->toBe('2026-12-31')
        ->and(Position::count())->toBe(1);
});

it('rejects an end date before the start date when editing', function () {
    $position = hold($this->fran, $this->org, 'CFO', ['start_date' => '2026-01-01']);

    Livewire::test(RelatedPositions::class, ['model' => $this->fran])
        ->call('edit', $position->id)
        ->set('end_date', '2025-12-31')
        ->call('save')
        ->assertHasErrors(['end_date']);

    expect($position->fresh()->end_date)->toBeNull();
});

it('ends and deletes a position', function () {
    $position = hold($this->fran, $this->org, 'CFO', ['start_date' => '2025-01-01']);

    $component = Livewire::test(RelatedPositions::class, ['model' => $this->fran])
        ->call('end', $position->id);

    expect($position->fresh()->end_date->toDateString())->toBe('2026-11-10');

    $component->call('delete', $position->id);

    expect(Position::count())->toBe(0);
});

it('cannot touch a position that belongs to another record', function () {
    $position = hold($this->casey, Organization::create(['name' => 'Other Co']), 'CEO');

    Livewire::test(RelatedPositions::class, ['model' => $this->fran])
        ->call('end', $position->id)
        ->call('delete', $position->id);

    expect($position->fresh()->end_date)->toBeNull();
});

// ---------------------------------------------------------------------
// Authorization
// ---------------------------------------------------------------------

it('forbids adding, ending and deleting without the edit people permission', function () {
    $position = hold($this->fran, $this->org, 'CFO');
    $this->actingAsUserWithPermissions(['view crm people']);

    Livewire::test(RelatedPositions::class, ['model' => $this->fran])
        ->call('add')->set('title', 'CEO')->call('save')->assertForbidden();
    Livewire::test(RelatedPositions::class, ['model' => $this->fran])
        ->call('end', $position->id)->assertForbidden();
    Livewire::test(RelatedPositions::class, ['model' => $this->fran])
        ->call('delete', $position->id)->assertForbidden();

    expect(Position::count())->toBe(1)
        ->and($position->fresh()->end_date)->toBeNull();
});

it('forbids every write while the module is off', function () {
    $position = hold($this->fran, $this->org, 'CFO');
    config()->set('laravel-crm.modules', ['leads']);
    $this->actingAsUserWithPermissions(['view crm people', 'edit crm people']);

    Livewire::test(RelatedPositions::class, ['model' => $this->fran])
        ->call('add')->set('title', 'CEO')->call('save')->assertForbidden();
    Livewire::test(RelatedPositions::class, ['model' => $this->fran])
        ->call('end', $position->id)->assertForbidden();

    expect(Position::count())->toBe(1);
});

// ---------------------------------------------------------------------
// People index
// ---------------------------------------------------------------------

it('finds a person by current position title on the people index', function () {
    hold($this->fran, $this->org, 'CFO');
    hold($this->casey, $this->org, 'Treasurer', ['start_date' => '2020-01-01', 'end_date' => '2021-01-01']);

    $names = fn (string $search) => tap(app(PersonIndex::class), fn ($c) => $c->search = $search)
        ->people()->pluck('first_name')->all();

    expect($names('cfo'))->toBe(['Fran'])
        ->and($names('Casey'))->toBe(['Casey'])
        ->and($names('treasurer'))->toBe([]);
});

it('finds a person by position title when names are encrypted', function () {
    config()->set('laravel-crm.encrypt_db_fields', true);
    $person = Person::create(['first_name' => 'Enid', 'last_name' => 'Crypt']);
    hold($person, $this->org, 'Company Secretary');

    $index = app(PersonIndex::class);
    $index->search = 'secretary';

    expect($index->people()->pluck('id')->all())->toBe([$person->id]);
});

it('filters the people index by position title and shows the column', function () {
    hold($this->fran, $this->org, 'CFO');

    Livewire::test(PersonIndex::class)
        ->assertSee('CFO')
        ->set('position_title', 'cf')
        ->assertSee('Fran')
        ->assertDontSee('Casey');
});

it('leaves positions out of the people index while the module is off', function () {
    hold($this->fran, $this->org, 'CFO');
    config()->set('laravel-crm.modules', ['leads']);

    $index = app(PersonIndex::class);
    $index->search = 'cfo';
    $index->position_title = 'cfo';

    expect($index->people()->total())->toBe(0)
        ->and(collect($index->headers())->pluck('key'))->not->toContain('current_position_title')
        ->and($index->people()->getCollection()->every(fn ($p) => ! $p->relationLoaded('currentPositions')))->toBeTrue();

    $index->search = '';
    expect($index->people()->total())->toBe(2);
});

it('titles the panel Position on a person and Positions on an organisation', function () {
    $title = fn ($model) => preg_match(
        '/>\s*(Positions?)\s*</',
        Livewire::test(RelatedPositions::class, ['model' => $model])->html(),
        $match
    ) ? $match[1] : null;

    expect($title($this->fran))->toBe('Position')
        ->and($title($this->org))->toBe('Positions');
});
