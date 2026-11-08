<?php

/*
 * remove_orphaned_custom_field_links_on_laravel_crm_field_models_table - removes
 * the model links and values left live by a custom field deleted before
 * FieldObserver cleaned them up, which otherwise break creating a record.
 */

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\FieldValue;
use VentureDrake\LaravelCrm\Models\Quote;

function runRemoveOrphanedFieldLinks(): void
{
    $migration = require __DIR__.'/../../database/updates/2026_11_08_000001_remove_orphaned_custom_field_links_on_laravel_crm_field_models_table.php';

    $migration->up();
}

/** A field on quotes with one quote holding a value for it. */
function quoteFieldWithValue(string $key, bool $deleted = false): Field
{
    $field = Field::create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'Custom '.$key,
        'key' => $key,
        'type' => 'text',
        'default' => $key,
    ]);

    FieldModel::create([
        'external_id' => Str::uuid()->toString(),
        'field_id' => $field->id,
        'model' => Quote::class,
    ]);

    Quote::create(['title' => 'A quote']);

    // As stored by a delete from before FieldObserver removed the links.
    if ($deleted) {
        Field::whereKey($field->id)->toBase()->update(['deleted_at' => now()]);
    }

    return $field;
}

test('the links and values of a deleted field are removed', function () {
    $field = quoteFieldWithValue('orphaned', deleted: true);

    runRemoveOrphanedFieldLinks();

    expect(FieldModel::where('field_id', $field->id)->exists())->toBeFalse()
        ->and(FieldModel::withTrashed()->where('field_id', $field->id)->exists())->toBeTrue()
        ->and(FieldValue::where('field_id', $field->id)->exists())->toBeFalse()
        ->and(FieldValue::withTrashed()->where('field_id', $field->id)->exists())->toBeTrue();
});

test('the links and values of a live field are left alone', function () {
    $field = quoteFieldWithValue('live');
    quoteFieldWithValue('orphaned', deleted: true);

    runRemoveOrphanedFieldLinks();

    expect(FieldModel::where('field_id', $field->id)->count())->toBe(1)
        ->and(FieldValue::where('field_id', $field->id)->count())->toBe(2);
});

test('a quote can be created once the orphans are removed', function () {
    quoteFieldWithValue('orphaned', deleted: true);

    runRemoveOrphanedFieldLinks();

    expect(Quote::create(['title' => 'Another quote'])->exists)->toBeTrue();
});

test('running it twice changes nothing more', function () {
    $live = quoteFieldWithValue('live');
    $orphaned = quoteFieldWithValue('orphaned', deleted: true);

    runRemoveOrphanedFieldLinks();
    $removedAt = FieldModel::withTrashed()->where('field_id', $orphaned->id)->value('deleted_at');

    $this->travel(1)->hour();
    runRemoveOrphanedFieldLinks();

    expect(FieldModel::withTrashed()->where('field_id', $orphaned->id)->value('deleted_at'))->toEqual($removedAt)
        ->and(FieldModel::where('field_id', $live->id)->count())->toBe(1)
        ->and(FieldValue::where('field_id', $live->id)->count())->toBe(2);
});
