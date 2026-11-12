<?php

use Illuminate\Support\Carbon;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Position;
use VentureDrake\LaravelCrm\Services\PositionService;

/*
 * A person leaving an organisation ends the positions they held there, and a
 * person or organisation being deleted takes its positions with it — but
 * only while the positions module is enabled.
 */

beforeEach(function () {
    Carbon::setTestNow('2026-11-10 09:00:00');

    $this->actingAsUser(['crm_access' => 1]);
    $this->org = Organization::create(['name' => 'JumpSkipAreo']);
    $this->other = Organization::create(['name' => 'Other Co']);
    $this->person = Person::create(['first_name' => 'Fran', 'organization_id' => $this->org->id]);

    $service = app(PositionService::class);
    $this->main = $service->create(['person_id' => $this->person->id, 'organization_id' => $this->org->id, 'title' => 'CFO', 'start_date' => '2025-01-01']);
    $this->board = $service->create(['person_id' => $this->person->id, 'organization_id' => $this->other->id, 'title' => 'Director', 'start_date' => '2025-01-01']);
});

afterEach(fn () => Carbon::setTestNow());

it('ends positions at the old organisation when the person moves', function () {
    $this->person->update(['organization_id' => $this->other->id]);

    expect($this->main->fresh()->end_date->toDateString())->toBe('2026-11-10')
        ->and($this->board->fresh()->end_date)->toBeNull();
});

it('ends positions at the old organisation when it is cleared', function () {
    $this->person->update(['organization_id' => null]);

    expect($this->main->fresh()->end_date)->not->toBeNull()
        ->and($this->board->fresh()->end_date)->toBeNull();
});

it('leaves positions alone when another field changes', function () {
    $this->person->update(['first_name' => 'Francis']);

    expect($this->main->fresh()->end_date)->toBeNull();
});

it('deletes all of a person\'s positions, past and current, when the person is deleted', function () {
    $past = Position::create(['person_id' => $this->person->id, 'organization_id' => $this->org->id, 'title' => 'Analyst', 'start_date' => '2020-01-01', 'end_date' => '2021-01-01']);

    $this->person->delete();

    expect(Position::where('person_id', $this->person->id)->count())->toBe(0)
        // Soft-deleted, like the person.
        ->and($this->main->fresh()->trashed())->toBeTrue()
        ->and($this->board->fresh()->trashed())->toBeTrue()
        ->and($past->fresh()->trashed())->toBeTrue();
});

it('deletes the positions at an organisation when it is deleted, and no others', function () {
    $this->org->delete();

    expect($this->main->fresh()->trashed())->toBeTrue()
        ->and($this->board->fresh()->trashed())->toBeFalse()
        ->and($this->board->fresh()->end_date)->toBeNull();
});

it('removes the positions for good when the person or organisation is', function () {
    $this->person->forceDelete();

    expect(Position::withTrashed()->where('person_id', $this->person->id)->count())->toBe(0);

    $other = Person::create(['first_name' => 'Pat', 'last_name' => 'Lee']);
    $kept = Position::create(['person_id' => $other->id, 'organization_id' => $this->org->id, 'title' => 'CFO', 'start_date' => '2024-01-01']);

    $this->org->forceDelete();

    expect(Position::withTrashed()->find($kept->id))->toBeNull();
});

it('touches no position while the module is disabled', function () {
    config()->set('laravel-crm.modules', ['leads']);

    $this->person->update(['organization_id' => $this->other->id]);
    $this->person->delete();
    $this->org->delete();

    expect(Position::whereNotNull('end_date')->count())->toBe(0);
});
