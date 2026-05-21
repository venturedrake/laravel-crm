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

test('public feature board is accessible to guests', function () {
    Feature::create(['title' => 'Public idea', 'is_public' => true]);

    $response = $this->get('/crm/p/features');

    $response->assertStatus(200);
    $response->assertSee('Public idea');
});

test('public feature board hides non-public features', function () {
    Feature::create(['title' => 'Private idea', 'is_public' => false]);

    $response = $this->get('/crm/p/features');

    $response->assertStatus(200);
    $response->assertDontSee('Private idea');
});

test('public feature show is accessible to guests', function () {
    $feature = Feature::create(['title' => 'Show me', 'is_public' => true]);

    $response = $this->get('/crm/p/features/'.$feature->external_id);

    $response->assertStatus(200);
    $response->assertSee('Show me');
});

test('public feature show 404s for non-public feature', function () {
    $feature = Feature::create(['title' => 'Hidden', 'is_public' => false]);

    $response = $this->get('/crm/p/features/'.$feature->external_id);

    $response->assertStatus(404);
});

test('guest clicking vote is redirected to portal login with intended URL', function () {
    $feature = Feature::create(['title' => 'Vote me', 'is_public' => true]);

    $response = $this->post('/crm/p/features/'.$feature->external_id.'/vote');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/crm/p/login');
    expect($response->headers->get('Location'))->toContain('intended=');
});

test('guest clicking submit is redirected to portal login', function () {
    $response = $this->get('/crm/p/features/submit');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/crm/p/login');
});

test('authenticated user can vote', function () {
    $user = User::create(['name' => 'Voter', 'email' => 'v@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);
    $feature = Feature::create(['title' => 'Cool', 'is_public' => true]);

    $response = $this->actingAs($user)->post('/crm/p/features/'.$feature->external_id.'/vote');

    $response->assertRedirect('/crm/p/features/'.$feature->external_id);
    expect((int) $feature->fresh()->votes_count)->toBe(1);
});

test('voting again is idempotent', function () {
    $user = User::create(['name' => 'Voter', 'email' => 'v2@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);
    $feature = Feature::create(['title' => 'Cool', 'is_public' => true]);

    $this->actingAs($user)->post('/crm/p/features/'.$feature->external_id.'/vote');
    $this->actingAs($user)->post('/crm/p/features/'.$feature->external_id.'/vote');

    expect((int) $feature->fresh()->votes_count)->toBe(1);
});

test('authenticated user can unvote via DELETE', function () {
    $user = User::create(['name' => 'Voter', 'email' => 'v3@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);
    $feature = Feature::create(['title' => 'Cool', 'is_public' => true]);

    $this->actingAs($user)->post('/crm/p/features/'.$feature->external_id.'/vote');
    expect((int) $feature->fresh()->votes_count)->toBe(1);

    $this->actingAs($user)->delete('/crm/p/features/'.$feature->external_id.'/vote');
    expect((int) $feature->fresh()->votes_count)->toBe(0);
});

test('feature votes have unique (feature_id, user_id) constraint', function () {
    $user = User::create(['name' => 'Voter', 'email' => 'v4@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);
    $feature = Feature::create(['title' => 'Cool', 'is_public' => true]);

    FeatureVote::create(['feature_id' => $feature->id, 'user_id' => $user->id]);

    expect(fn () => FeatureVote::create(['feature_id' => $feature->id, 'user_id' => $user->id]))
        ->toThrow(QueryException::class);
});

test('authenticated user can submit a feature', function () {
    $user = User::create(['name' => 'Submitter', 'email' => 's@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);

    $response = $this->actingAs($user)->post('/crm/p/features/submit', [
        'title' => 'My idea',
        'description' => 'Detailed description',
    ]);

    $response->assertRedirect();

    $feature = Feature::where('title', 'My idea')->first();
    expect($feature)->not->toBeNull();
    expect($feature->submitted_by_user_id)->toBe($user->id);
    expect((bool) $feature->is_public)->toBeTrue();
    expect($feature->feature_status_id)->not->toBeNull();
});

test('authenticated user can post a comment', function () {
    $user = User::create(['name' => 'Commenter', 'email' => 'c@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);
    $feature = Feature::create(['title' => 'Discuss', 'is_public' => true]);

    $response = $this->actingAs($user)->post('/crm/p/features/'.$feature->external_id.'/comments', [
        'body' => 'I support this',
    ]);

    $response->assertRedirect('/crm/p/features/'.$feature->external_id);

    $comment = FeatureComment::where('feature_id', $feature->id)->first();
    expect($comment)->not->toBeNull();
    expect($comment->body)->toBe('I support this');
    expect($comment->user_created_id)->toBe($user->id);
    expect($comment->is_admin_reply)->toBeFalse();
});

test('admin reply renders with is_admin_reply true', function () {
    $admin = User::create(['name' => 'Admin', 'email' => 'a@example.com', 'password' => bcrypt('x'), 'crm_access' => 1]);
    $feature = Feature::create(['title' => 'Discuss', 'is_public' => true]);

    $this->actingAs($admin)->post('/crm/p/features/'.$feature->external_id.'/comments', [
        'body' => 'Official response',
    ]);

    $comment = FeatureComment::where('feature_id', $feature->id)->first();
    expect($comment->is_admin_reply)->toBeTrue();

    $response = $this->get('/crm/p/features/'.$feature->external_id);
    $response->assertSee('Official response');
    $response->assertSee('Admin');
});

test('status filter via query string works', function () {
    $statusA = FeatureStatus::create(['name' => 'Considering', 'order' => 2]);
    $statusB = FeatureStatus::create(['name' => 'Planned', 'order' => 3]);

    Feature::create(['title' => 'Idea A', 'is_public' => true, 'feature_status_id' => $statusA->id]);
    Feature::create(['title' => 'Idea B', 'is_public' => true, 'feature_status_id' => $statusB->id]);

    $response = $this->get('/crm/p/features?status='.$statusA->id);

    $response->assertStatus(200);
    $response->assertSee('Idea A');
    $response->assertDontSee('Idea B');
});
