<?php

/*
 * Restoring a soft-deleted record clears who deleted it and records who
 * restored it. LunchObserver and FileObserver used to write the restoring
 * user into user_deleted_id, so a restored record still read as deleted by
 * them. ProductObserver had the same bug - see tests/Feature/Models/ProductTest.php.
 */

use VentureDrake\LaravelCrm\Models\File;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Person;

beforeEach(function () {
    // These observers only stamp users outside the console, as in a web request.
    (fn () => $this->isRunningInConsole = false)->call(app());
});

dataset('restorable records', [
    'lunch' => [fn () => Lunch::create([
        'name' => 'Team lunch',
        'start_at' => '2026-10-21 12:00:00',
        'finish_at' => '2026-10-21 13:00:00',
    ])],
    'file' => [function () {
        $person = Person::create(['first_name' => 'Owner']);

        return File::create([
            'file' => 'laravel-crm/person/'.$person->id.'/files/doc.pdf',
            'name' => 'doc.pdf',
            'disk' => 'local',
            'fileable_type' => $person->getMorphClass(),
            'fileable_id' => $person->id,
        ]);
    }],
]);

test('restoring clears user_deleted_id and sets user_restored_id', function (Closure $make) {
    $deleter = $this->actingAsUser();
    $record = $make();
    $record->delete();

    expect($record->fresh()->user_deleted_id)->toBe($deleter->id);

    $restorer = $this->actingAsUser();
    $record::withTrashed()->find($record->id)->restore();

    $restored = $record->fresh();
    expect($restored->deleted_at)->toBeNull();
    expect($restored->user_deleted_id)->toBeNull();
    expect($restored->user_restored_id)->toBe($restorer->id);
})->with('restorable records');
