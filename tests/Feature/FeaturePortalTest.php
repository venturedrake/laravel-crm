<?php

use Illuminate\Database\QueryException;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureComment;
use VentureDrake\LaravelCrm\Models\FeatureStatus;
use VentureDrake\LaravelCrm\Models\FeatureVote;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

beforeEach(function () {
    config()->set('laravel-crm.modules', ['features']);
    FeatureStatus::firstOrCreate(['name' => 'New'], ['is_default' => true, 'order' => 1, 'color' => '#6c757d']);
});

test('guest can view the public feature board', function () {
    Feature::create(['title' => 'Visible to all', 'is_public' => true]);

    $response = $this->get('/crm/p/features');

    $response->assertStatus(200);
    $response->assertSee('Visible to all');
});

test('guest can view a public feature show page', function () {
    $feature = Feature::create(['title' => 'Open idea', 'is_public' => true]);

    $response = $this->get('/crm/p/features/'.$feature->external_id);

    $response->assertStatus(200);
    $response->assertSee('Open idea');
});

test('guest voting is redirected to portal login', function () {
    $feature = Feature::create(['title' => 'Need auth to vote', 'is_public' => true]);

    $response = $this->post('/crm/p/features/'.$feature->external_id.'/vote');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/crm/p/login');
    expect((int) $feature->fresh()->votes_count)->toBe(0);
});

test('guest commenting is redirected to portal login', function () {
    $feature = Feature::create(['title' => 'Need auth to comment', 'is_public' => true]);

    $response = $this->post('/crm/p/features/'.$feature->external_id.'/comments', [
        'body' => 'Hi',
    ]);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/crm/p/login');
    expect(FeatureComment::where('feature_id', $feature->id)->count())->toBe(0);
});

test('guest submitting a feature is redirected to portal login', function () {
    $response = $this->get('/crm/p/features/submit');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/crm/p/login');
});

test('authenticated user can vote and votes_count increments', function () {
    $user = User::create(['name' => 'V', 'email' => 'v@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);
    $feature = Feature::create(['title' => 'Vote target', 'is_public' => true]);

    $this->actingAs($user)->post('/crm/p/features/'.$feature->external_id.'/vote');

    expect((int) $feature->fresh()->votes_count)->toBe(1);
});

test('one vote per user is enforced at the database level', function () {
    $user = User::create(['name' => 'V', 'email' => 'unique@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);
    $feature = Feature::create(['title' => 'Uniqueness', 'is_public' => true]);

    FeatureVote::create(['feature_id' => $feature->id, 'user_id' => $user->id]);

    expect(fn () => FeatureVote::create(['feature_id' => $feature->id, 'user_id' => $user->id]))
        ->toThrow(QueryException::class);
});

test('voting twice via the portal is idempotent', function () {
    $user = User::create(['name' => 'V', 'email' => 'idem@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);
    $feature = Feature::create(['title' => 'Idem', 'is_public' => true]);

    $this->actingAs($user)->post('/crm/p/features/'.$feature->external_id.'/vote');
    $this->actingAs($user)->post('/crm/p/features/'.$feature->external_id.'/vote');

    expect((int) $feature->fresh()->votes_count)->toBe(1);
});

test('vote toggle (unvote) decrements votes_count', function () {
    $user = User::create(['name' => 'V', 'email' => 'toggle@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);
    $feature = Feature::create(['title' => 'Toggle', 'is_public' => true]);

    $this->actingAs($user)->post('/crm/p/features/'.$feature->external_id.'/vote');
    expect((int) $feature->fresh()->votes_count)->toBe(1);

    $this->actingAs($user)->delete('/crm/p/features/'.$feature->external_id.'/vote');
    expect((int) $feature->fresh()->votes_count)->toBe(0);
});

test('authenticated user can submit a feature', function () {
    $user = User::create(['name' => 'S', 'email' => 's@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);

    $this->actingAs($user)->post('/crm/p/features/submit', [
        'title' => 'Portal idea',
        'description' => 'detailed',
    ]);

    $feature = Feature::where('title', 'Portal idea')->first();

    expect($feature)->not->toBeNull();
    expect($feature->submitted_by_user_id)->toBe($user->id);
});

test('authenticated user can post a comment', function () {
    $user = User::create(['name' => 'C', 'email' => 'c@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);
    $feature = Feature::create(['title' => 'Discuss', 'is_public' => true]);

    $this->actingAs($user)->post('/crm/p/features/'.$feature->external_id.'/comments', [
        'body' => 'My comment',
    ]);

    $comment = FeatureComment::where('feature_id', $feature->id)->first();

    expect($comment)->not->toBeNull();
    expect($comment->body)->toBe('My comment');
    expect($comment->user_created_id)->toBe($user->id);
});
