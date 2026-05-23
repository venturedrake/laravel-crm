<?php

use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureStatus;
use VentureDrake\LaravelCrm\Services\FeatureService;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

beforeEach(function () {
    config()->set('laravel-crm.modules', ['features']);
});

test('service creates a feature with minimum data', function () {
    FeatureStatus::create(['name' => 'New', 'is_default' => true, 'order' => 1]);

    $feature = app(FeatureService::class)->create([
        'title' => 'New feature request',
        'description' => 'Allow drag-and-drop sorting',
    ]);

    expect($feature)->toBeInstanceOf(Feature::class);
    expect($feature->title)->toBe('New feature request');
    expect($feature->description)->toBe('Allow drag-and-drop sorting');
    expect($feature->feature_id)->toStartWith('F');
    expect($feature->feature_status_id)->not->toBeNull();
});

test('service records submitter when user passed in', function () {
    $user = User::create(['name' => 'Submitter', 'email' => 'submitter@example.com']);

    $feature = app(FeatureService::class)->create([
        'title' => 'Linked',
    ], $user);

    expect($feature->submitted_by_user_id)->toBe($user->id);
});

test('service updates an existing feature', function () {
    $feature = Feature::create(['title' => 'Old', 'description' => 'old text']);

    app(FeatureService::class)->update($feature, [
        'title' => 'Renamed',
        'description' => 'fresh',
    ]);

    $fresh = $feature->fresh();

    expect($fresh->title)->toBe('Renamed');
    expect($fresh->description)->toBe('fresh');
});

test('vote is idempotent', function () {
    $feature = Feature::create(['title' => 'Vote me']);
    $user = User::create(['name' => 'Voter', 'email' => 'voter@example.com']);

    $service = app(FeatureService::class);

    $service->vote($feature, $user);
    $service->vote($feature, $user);
    $service->vote($feature, $user);

    expect((int) $feature->fresh()->votes_count)->toBe(1);
});

test('unvote removes a vote and decrements the count', function () {
    $feature = Feature::create(['title' => 'Vote me']);
    $user = User::create(['name' => 'Voter', 'email' => 'voter2@example.com']);

    $service = app(FeatureService::class);
    $service->vote($feature, $user);

    expect((int) $feature->fresh()->votes_count)->toBe(1);

    expect($service->unvote($feature, $user))->toBeTrue();
    expect((int) $feature->fresh()->votes_count)->toBe(0);

    // Calling unvote again is a no-op.
    expect($service->unvote($feature, $user))->toBeFalse();
});

test('comment flags is_admin_reply true for crm admin with edit permission', function () {
    $feature = Feature::create(['title' => 'Discuss']);
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'crm_access' => true,
        'crm_permissions' => json_encode(['edit crm features']),
    ]);

    $comment = app(FeatureService::class)->comment($feature, $admin, 'Internal note');

    expect($comment->is_admin_reply)->toBeTrue();
    expect($comment->body)->toBe('Internal note');
    expect($comment->user_created_id)->toBe($admin->id);
    expect((int) $feature->fresh()->comments_count)->toBe(1);
});

test('comment flags is_admin_reply false for non-crm user', function () {
    $feature = Feature::create(['title' => 'Discuss']);
    $external = User::create(['name' => 'External', 'email' => 'external@example.com', 'crm_access' => false]);

    $comment = app(FeatureService::class)->comment($feature, $external, 'Customer voice');

    expect($comment->is_admin_reply)->toBeFalse();
});

test('comment flags is_admin_reply false for crm user lacking feature edit permission', function () {
    $feature = Feature::create(['title' => 'Discuss']);
    $user = User::create([
        'name' => 'Viewer',
        'email' => 'viewer@example.com',
        'crm_access' => true,
        'crm_permissions' => json_encode(['view crm features']),
    ]);

    $comment = app(FeatureService::class)->comment($feature, $user, 'I can only view');

    expect($comment->is_admin_reply)->toBeFalse();
});
