<?php

/*
 * Issue #100 - deleting a custom field from the settings pages left its model
 * links live, so creating any record of a linked model read the default of a
 * field that was gone and failed.
 */

use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Settings\CustomFields\CustomFieldIndex;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\FieldValue;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Quote;

function attachedField(string $model, string $key, ?string $default = null): Field
{
    $field = Field::create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'Custom '.$key,
        'key' => $key,
        'type' => 'text',
        'default' => $default,
    ]);

    FieldModel::create([
        'external_id' => Str::uuid()->toString(),
        'field_id' => $field->id,
        'model' => $model,
    ]);

    return $field;
}

function createRecord(string $model)
{
    return match ($model) {
        Lead::class => Lead::create(['title' => 'A lead', 'currency' => 'USD']),
        Quote::class => Quote::create(['title' => 'A quote']),
    };
}

test('deleting a field removes its model links and values only', function () {
    $deleted = attachedField(Quote::class, 'deleted', 'gone');
    $kept = attachedField(Quote::class, 'kept', 'stays');
    $quote = createRecord(Quote::class);

    expect($quote->fields()->count())->toBe(2);

    $deleted->delete();

    expect(FieldModel::where('field_id', $deleted->id)->exists())->toBeFalse()
        ->and(FieldModel::withTrashed()->where('field_id', $deleted->id)->exists())->toBeTrue()
        ->and(FieldValue::where('field_id', $deleted->id)->exists())->toBeFalse()
        ->and(FieldValue::withTrashed()->where('field_id', $deleted->id)->exists())->toBeTrue()
        ->and(FieldModel::where('field_id', $kept->id)->count())->toBe(1)
        ->and($quote->fields()->pluck('value')->all())->toBe(['stays']);
});

test('a record can still be created after its custom field is deleted', function (string $model) {
    $deleted = attachedField($model, 'deleted', 'gone');
    $kept = attachedField($model, 'kept', 'stays');

    $deleted->delete();

    $record = createRecord($model);

    expect($record->exists)->toBeTrue()
        ->and($record->fields()->pluck('value', 'field_id')->all())->toBe([$kept->id => 'stays']);
})->with([
    'quote' => Quote::class,
    'lead' => Lead::class,
]);

test('deleting a field from the settings list leaves quote creation working', function () {
    $this->actingAsUserWithPermissions(['delete crm fields']);
    $field = attachedField(Quote::class, 'deleted', 'gone');

    Livewire::test(CustomFieldIndex::class)
        ->call('delete', $field->id)
        ->assertHasNoErrors();

    expect(Field::find($field->id))->toBeNull()
        ->and(FieldModel::where('field_id', $field->id)->exists())->toBeFalse();

    $quote = createRecord(Quote::class);

    expect($quote->exists)->toBeTrue()
        ->and($quote->fields()->count())->toBe(0);
});

test('a link left pointing at a deleted field is skipped on create', function (string $model) {
    $orphaned = attachedField($model, 'orphaned', 'gone');
    $kept = attachedField($model, 'kept', 'stays');

    // As stored by a delete from before FieldObserver removed the links.
    Field::whereKey($orphaned->id)->toBase()->update(['deleted_at' => now()]);

    $record = createRecord($model);

    expect($record->fields()->pluck('value', 'field_id')->all())->toBe([$kept->id => 'stays']);
})->with([
    'quote' => Quote::class,
    'lead' => Lead::class,
]);

test('laravelcrm:fields skips a link left pointing at a deleted field', function () {
    $orphaned = attachedField(Quote::class, 'orphaned', 'gone');
    $kept = attachedField(Quote::class, 'kept', 'stays');
    createRecord(Quote::class);

    Field::whereKey($orphaned->id)->toBase()->update(['deleted_at' => now()]);

    $this->artisan('laravelcrm:fields')->assertSuccessful();

    expect(FieldValue::where('field_id', $kept->id)->count())->toBe(1);
});
