<?php

use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureStatus;
use VentureDrake\LaravelCrm\Models\FeatureView;
use VentureDrake\LaravelCrm\Services\FeatureService;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

beforeEach(function () {
    config()->set('laravel-crm.modules', ['features']);
    FeatureStatus::firstOrCreate(['name' => 'New'], ['is_default' => true, 'order' => 1, 'color' => '#6c757d']);
});

test('recordView inserts a FeatureView row and increments views_count', function () {
    $feature = Feature::create(['title' => 'Tracked', 'is_public' => true]);
    $user = User::create(['name' => 'Viewer', 'email' => 'view1@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);

    app(FeatureService::class)->recordView($feature, $user, '203.0.113.5');

    expect(FeatureView::where('feature_id', $feature->id)->count())->toBe(1);
    expect((int) $feature->fresh()->views_count)->toBe(1);

    $view = FeatureView::where('feature_id', $feature->id)->first();
    expect($view->user_id)->toBe($user->id);
    expect($view->ip_hash)->toBe(hash('sha256', '203.0.113.5'));
});

test('anonymous view records user_id null', function () {
    $feature = Feature::create(['title' => 'Anon', 'is_public' => true]);

    app(FeatureService::class)->recordView($feature, null, '198.51.100.1');

    $view = FeatureView::where('feature_id', $feature->id)->first();
    expect($view->user_id)->toBeNull();
    expect($view->ip_hash)->toBe(hash('sha256', '198.51.100.1'));
});

test('visiting the public show page increments views_count', function () {
    $feature = Feature::create(['title' => 'Visit me', 'is_public' => true]);

    $this->get('/p/features/'.$feature->external_id)->assertStatus(200);
    $this->get('/p/features/'.$feature->external_id)->assertStatus(200);

    expect((int) $feature->fresh()->views_count)->toBe(2);
    expect(FeatureView::where('feature_id', $feature->id)->count())->toBe(2);
});

test('a non-public feature 404s and records no view', function () {
    $feature = Feature::create(['title' => 'Hidden', 'is_public' => false]);

    $this->get('/p/features/'.$feature->external_id)->assertStatus(404);

    expect((int) $feature->fresh()->views_count)->toBe(0);
    expect(FeatureView::where('feature_id', $feature->id)->count())->toBe(0);
});
