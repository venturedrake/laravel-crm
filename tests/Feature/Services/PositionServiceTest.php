<?php

use Illuminate\Support\Carbon;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Position;
use VentureDrake\LaravelCrm\Services\PositionService;

/*
 * A position is one job a person holds at an organisation. Adding one
 * ends the positions it replaces — the person's earlier position there, and the
 * same title held there by someone else — unless the caller keeps them open.
 */

beforeEach(function () {
    Carbon::setTestNow('2026-11-10 09:00:00');

    $this->actingAsUser(['crm_access' => 1]);
    $this->service = app(PositionService::class);
    $this->org = Organization::create(['name' => 'JumpSkipAreo']);
    $this->ceo = Person::create(['first_name' => 'Casey', 'last_name' => 'Eo']);
    $this->cfo = Person::create(['first_name' => 'Fran', 'last_name' => 'Ops']);
});

afterEach(fn () => Carbon::setTestNow());

function position(Person $person, Organization $org, string $title, array $extra = []): Position
{
    return app(PositionService::class)->create(array_merge([
        'person_id' => $person->id,
        'organization_id' => $org->id,
        'title' => $title,
    ], $extra));
}

it('starts a position today when no start date is given', function () {
    $position = position($this->ceo, $this->org, 'CEO');

    expect($position->start_date->toDateString())->toBe('2026-11-10')
        ->and($position->end_date)->toBeNull()
        ->and($position->isCurrent())->toBeTrue()
        ->and($position->external_id)->not->toBeEmpty();
});

it('trims the title', function () {
    expect(position($this->ceo, $this->org, '  CEO ')->title)->toBe('CEO');
});

it('ends the earlier position when a person changes position at the same organisation', function () {
    $first = position($this->cfo, $this->org, 'Financial Controller', ['start_date' => '2024-01-01']);
    $second = position($this->cfo, $this->org, 'CFO', ['start_date' => '2026-06-01']);

    expect($first->fresh()->end_date->toDateString())->toBe('2026-06-01')
        ->and($first->fresh()->isCurrent())->toBeFalse()
        ->and($second->isCurrent())->toBeTrue()
        ->and($this->cfo->positions()->count())->toBe(2)
        ->and($this->cfo->currentPositions()->pluck('title')->all())->toBe(['CFO']);
});

it('leaves positions at other organisations alone', function () {
    $board = position($this->cfo, Organization::create(['name' => 'Other Co']), 'Director');
    position($this->cfo, $this->org, 'CFO');

    expect($board->fresh()->end_date)->toBeNull();
});

it('ends the earlier holder when the same title is given to someone else', function () {
    $old = position($this->cfo, $this->org, 'CFO', ['start_date' => '2023-03-01']);
    $new = position($this->ceo, $this->org, ' cfo ', ['start_date' => '2026-11-01']);

    expect($old->fresh()->end_date->toDateString())->toBe('2026-11-01')
        ->and($new->isCurrent())->toBeTrue()
        ->and($this->org->currentPositions()->count())->toBe(1);
});

it('does not end the same title at a different organisation', function () {
    $elsewhere = position($this->cfo, Organization::create(['name' => 'Other Co']), 'CFO');
    position($this->ceo, $this->org, 'CFO');

    expect($elsewhere->fresh()->end_date)->toBeNull();
});

it('keeps open the positions the caller did not choose to end', function () {
    $gmSales = position($this->ceo, $this->org, 'GM');
    $gmOps = $this->service->create([
        'person_id' => $this->cfo->id,
        'organization_id' => $this->org->id,
        'title' => 'GM',
    ], []);

    $third = Person::create(['first_name' => 'Gia', 'last_name' => 'Em']);
    $conflicts = $this->service->conflicts($third->id, $this->org->id, 'GM');

    expect($conflicts->pluck('id')->sort()->values()->all())->toBe([$gmSales->id, $gmOps->id]);

    $this->service->create([
        'person_id' => $third->id,
        'organization_id' => $this->org->id,
        'title' => 'GM',
    ], [$gmOps->id]);

    expect($gmSales->fresh()->end_date)->toBeNull()
        ->and($gmOps->fresh()->end_date)->not->toBeNull()
        ->and($this->org->currentPositions()->count())->toBe(2);
});

it('ends nothing when given an empty list', function () {
    $first = position($this->cfo, $this->org, 'Director');

    $this->service->create([
        'person_id' => $this->cfo->id,
        'organization_id' => $this->org->id,
        'title' => 'CFO',
    ], []);

    expect($first->fresh()->end_date)->toBeNull()
        ->and($this->cfo->currentPositions()->count())->toBe(2);
});

it('ignores ids that are not conflicts', function () {
    $unrelated = position($this->ceo, $this->org, 'CEO');

    $this->service->create([
        'person_id' => $this->cfo->id,
        'organization_id' => $this->org->id,
        'title' => 'CFO',
    ], [$unrelated->id]);

    expect($unrelated->fresh()->end_date)->toBeNull();
});

it('never ends a position before it started', function () {
    $later = position($this->cfo, $this->org, 'CFO', ['start_date' => '2026-10-01']);

    // Back-dated position entered afterwards.
    position($this->cfo, $this->org, 'Analyst', ['start_date' => '2020-01-01']);

    expect($later->fresh()->end_date->toDateString())->toBe('2026-10-01');
});

it('does not list ended positions as conflicts', function () {
    $ended = position($this->cfo, $this->org, 'CFO');
    $this->service->end($ended);

    expect($this->service->conflicts($this->ceo->id, $this->org->id, 'CFO'))->toBeEmpty();
});

it('ends a position today and can reopen it', function () {
    $position = position($this->ceo, $this->org, 'CEO', ['start_date' => '2025-01-01']);

    $this->service->end($position);
    expect($position->fresh()->end_date->toDateString())->toBe('2026-11-10')
        ->and($position->fresh()->isCurrent())->toBeFalse();

    $this->service->update($position, ['end_date' => null]);
    expect($position->fresh()->isCurrent())->toBeTrue();
});

it('treats a future end date as still current', function () {
    $position = position($this->ceo, $this->org, 'CEO', ['end_date' => '2026-12-31']);

    expect($position->isCurrent())->toBeTrue()
        ->and(Position::current()->count())->toBe(1)
        ->and(Position::past()->count())->toBe(0);
});

it('soft deletes a position', function () {
    $position = position($this->ceo, $this->org, 'CEO');

    $this->service->delete($position);

    expect(Position::count())->toBe(0)
        ->and(Position::withTrashed()->count())->toBe(1);
});

it('suggests titles already used, most used first', function () {
    position($this->ceo, $this->org, 'CEO');
    position($this->cfo, $this->org, 'CFO');
    position(Person::create(['first_name' => 'A']), Organization::create(['name' => 'B']), 'CFO');

    expect($this->service->titleSuggestions()->all())->toBe(['CFO', 'CEO'])
        ->and($this->service->titleSuggestions('ce')->all())->toBe(['CEO']);
});

it('stores the title in plain text when field encryption is on', function () {
    config()->set('laravel-crm.encrypt_db_fields', true);

    $position = position($this->ceo, $this->org, 'CEO');

    expect($position->getRawOriginal('title'))->toBe('CEO')
        ->and(Position::where('title', 'CEO')->count())->toBe(1);
});

it('links the organisation to a person who has none when a current position is added', function () {
    position($this->ceo, $this->org, 'CEO');

    expect((int) $this->ceo->fresh()->organization_id)->toBe($this->org->id);
});

it('keeps the organisation of a person who already has one', function () {
    $other = Organization::create(['name' => 'Other Co']);
    $this->ceo->update(['organization_id' => $this->org->id]);
    $ceo = position($this->ceo, $this->org, 'CEO');

    position($this->ceo, $other, 'Board member');

    expect((int) $this->ceo->fresh()->organization_id)->toBe($this->org->id)
        ->and($ceo->fresh()->end_date)->toBeNull();
});

it('does not link the organisation for a position that has already ended', function () {
    position($this->ceo, $this->org, 'CEO', ['start_date' => '2026-01-01', 'end_date' => '2026-06-01']);

    expect($this->ceo->fresh()->organization_id)->toBeNull();
});

it('keeps the organisation link when a position is ended or deleted', function () {
    $ceo = position($this->ceo, $this->org, 'CEO');
    $chair = position($this->ceo, $this->org, 'Chair');

    $this->service->end($ceo->fresh());
    $this->service->delete($chair->fresh());

    expect((int) $this->ceo->fresh()->organization_id)->toBe($this->org->id);
});

it('adds the related organisation and related person links for a current position', function () {
    position($this->ceo, $this->org, 'CEO');

    expect($this->ceo->contacts()->where('entityable_type', $this->org->getMorphClass())->where('entityable_id', $this->org->id)->count())->toBe(1)
        ->and($this->org->contacts()->where('entityable_type', $this->ceo->getMorphClass())->where('entityable_id', $this->ceo->id)->count())->toBe(1);
});

it('does not add the links a second time', function () {
    position($this->ceo, $this->org, 'CEO');
    position($this->ceo, $this->org, 'Chair');

    expect($this->ceo->contacts()->count())->toBe(1)
        ->and($this->org->contacts()->count())->toBe(1);
});

it('links a second organisation without replacing the first', function () {
    $other = Organization::create(['name' => 'Other Co']);
    position($this->ceo, $this->org, 'CEO');
    position($this->ceo, $other, 'Board member');

    expect($this->ceo->contacts()->pluck('entityable_id')->sort()->values()->all())->toBe([$this->org->id, $other->id])
        ->and((int) $this->ceo->fresh()->organization_id)->toBe($this->org->id);
});

it('keeps the related links when a position is ended or deleted', function () {
    $ceo = position($this->ceo, $this->org, 'CEO');

    $this->service->end($ceo->fresh());
    $this->service->delete($ceo->fresh());

    expect($this->ceo->contacts()->count())->toBe(1)
        ->and($this->org->contacts()->count())->toBe(1);
});

it('adds no related links for a position that has already ended', function () {
    position($this->ceo, $this->org, 'CEO', ['start_date' => '2026-01-01', 'end_date' => '2026-06-01']);

    expect($this->ceo->contacts()->count())->toBe(0);
});
