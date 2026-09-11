<?php

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\People\PersonIndex;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Person;

/*
 * The people index used to render each row by querying for it: labels, the
 * primary email, the primary phone, the whole deals collection and the owner —
 * five queries per row, 125 on a full page. On a database a network hop away
 * that is most of a second of pure round-trip latency.
 *
 * Two assertions here, because either alone can pass while the defect is back:
 * an absolute ceiling catches a new relation being touched per row, and the
 * "does not grow with the number of rows" pair catches an N+1 that a shifting
 * baseline would otherwise hide.
 */

beforeEach(function () {
    $this->actingAsUser(['crm_access' => 1]);
});

/**
 * Seed `$count` people, each carrying every relation the index renders: a
 * label, a primary email, a primary phone and one deal in each closed state.
 */
function seedPeopleForIndex(int $count): void
{
    $label = Label::create(['name' => 'Hot', 'hex' => 'ff0000']);

    for ($i = 0; $i < $count; $i++) {
        $person = Person::create(['first_name' => 'Person', 'last_name' => (string) $i]);

        $person->emails()->create(['address' => "person{$i}@example.com", 'primary' => 1]);
        $person->phones()->create(['number' => '55500'.$i, 'primary' => 1]);
        $person->labels()->attach($label);

        Deal::create(['title' => "Open {$i}", 'person_id' => $person->id]);
        Deal::create(['title' => "Won {$i}", 'person_id' => $person->id, 'closed_status' => 'won', 'closed_at' => now()]);
        Deal::create(['title' => "Lost {$i}", 'person_id' => $person->id, 'closed_status' => 'lost', 'closed_at' => now()]);
    }
}

/**
 * Queries issued by a full mount + render of the index.
 */
function personIndexQueryCount(): int
{
    $count = 0;

    DB::listen(function () use (&$count) {
        $count++;
    });

    Livewire::test(PersonIndex::class)->assertOk();

    return $count;
}

it('renders a full page of people within a bounded number of queries', function () {
    seedPeopleForIndex(25);

    expect(personIndexQueryCount())->toBeLessThanOrEqual(15);
});

it('costs the same number of queries whether the page holds 5 rows or 25', function () {
    seedPeopleForIndex(5);

    // A throwaway render first: SettingService memoises its schema probe and
    // settings map for the life of the container, so the very first render of
    // the process is one query dearer than every one after it.
    personIndexQueryCount();

    $fiveRows = personIndexQueryCount();

    seedPeopleForIndex(20);
    $twentyFiveRows = personIndexQueryCount();

    expect($twentyFiveRows)->toBe($fiveRows);
});

it('counts open, won and lost deals per person as aggregates', function () {
    seedPeopleForIndex(1);

    $person = app(PersonIndex::class)->people()->first();

    expect($person->open_deals_count)->toBe(1)
        ->and($person->won_deals_count)->toBe(1)
        ->and($person->lost_deals_count)->toBe(1);
});

it('reads the primary email and phone off the eager-loaded relations', function () {
    seedPeopleForIndex(1);

    $person = app(PersonIndex::class)->people()->first();

    expect($person->relationLoaded('primaryEmail'))->toBeTrue()
        ->and($person->relationLoaded('primaryPhone'))->toBeTrue()
        ->and($person->primaryEmail->address)->toBe('person0@example.com')
        ->and($person->primaryPhone->number)->toBe('555000');

    // getPrimaryEmail()/getPrimaryPhone() keep working for every other caller,
    // and cost nothing once the relation is loaded.
    DB::flushQueryLog();
    DB::enableQueryLog();

    expect($person->getPrimaryEmail()->address)->toBe('person0@example.com')
        ->and($person->getPrimaryPhone()->number)->toBe('555000')
        ->and(DB::getQueryLog())->toBe([]);

    DB::disableQueryLog();
});

it('still resolves the primary email when nothing eager loaded it', function () {
    seedPeopleForIndex(1);

    $person = Person::first();

    expect($person->relationLoaded('primaryEmail'))->toBeFalse()
        ->and($person->getPrimaryEmail()->address)->toBe('person0@example.com')
        ->and($person->getPrimaryPhone()->number)->toBe('555000');
});
