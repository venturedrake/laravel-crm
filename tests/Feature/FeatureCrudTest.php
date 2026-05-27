<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Features\FeatureCreate;
use VentureDrake\LaravelCrm\Livewire\Features\FeatureEdit;
use VentureDrake\LaravelCrm\Livewire\Features\FeatureIndex;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureStatus;

beforeEach(function () {
    config()->set('laravel-crm.modules', ['features']);
    $this->actingAsUser(['crm_access' => 1]);
});

test('admin can create a feature via Livewire and default status is assigned', function () {
    $default = FeatureStatus::create(['name' => 'New', 'is_default' => true, 'order' => 1, 'color' => '#6c757d']);

    Livewire::test(FeatureCreate::class)
        ->set('title', 'Drag-and-drop kanban')
        ->set('description', 'Let users reorder cards')
        ->set('is_public', true)
        ->call('save');

    $feature = Feature::where('title', 'Drag-and-drop kanban')->first();

    expect($feature)->not->toBeNull();
    expect($feature->description)->toBe('Let users reorder cards');
    expect((bool) $feature->is_public)->toBeTrue();
    expect($feature->feature_status_id)->toBe($default->id);
    expect($feature->feature_id)->toStartWith('F');
});

test('admin create validates required title', function () {
    FeatureStatus::create(['name' => 'New', 'is_default' => true, 'order' => 1]);

    Livewire::test(FeatureCreate::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title' => 'required']);
});

test('admin can edit a feature via Livewire', function () {
    FeatureStatus::create(['name' => 'New', 'is_default' => true, 'order' => 1]);
    $status = FeatureStatus::create(['name' => 'Planned', 'order' => 2]);

    $feature = Feature::create(['title' => 'Old title', 'description' => 'old', 'is_public' => false]);

    Livewire::test(FeatureEdit::class, ['feature' => $feature])
        ->set('title', 'New title')
        ->set('description', 'updated body')
        ->set('is_public', true)
        ->set('feature_status_id', $status->id)
        ->call('save');

    $fresh = $feature->fresh();

    expect($fresh->title)->toBe('New title');
    expect($fresh->description)->toBe('updated body');
    expect((bool) $fresh->is_public)->toBeTrue();
    expect($fresh->feature_status_id)->toBe($status->id);
});

test('admin can delete a feature via Livewire index', function () {
    FeatureStatus::create(['name' => 'New', 'is_default' => true, 'order' => 1]);
    $feature = Feature::create(['title' => 'To remove', 'is_public' => true]);

    Livewire::test(FeatureIndex::class)
        ->call('delete', $feature->id);

    expect(Feature::find($feature->id))->toBeNull();
    expect(Feature::withTrashed()->find($feature->id))->not->toBeNull();
});

test('index lists features and respects search filter', function () {
    FeatureStatus::create(['name' => 'New', 'is_default' => true, 'order' => 1]);

    Feature::create(['title' => 'Apples', 'is_public' => true]);
    Feature::create(['title' => 'Bananas', 'is_public' => true]);

    Livewire::test(FeatureIndex::class)
        ->set('search', 'Apple')
        ->assertSee('Apples')
        ->assertDontSee('Bananas');
});
