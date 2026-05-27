<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Features\FeatureShow;
use VentureDrake\LaravelCrm\Livewire\Features\FeatureVoters;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureStatus;
use VentureDrake\LaravelCrm\Models\FeatureView;
use VentureDrake\LaravelCrm\Models\FeatureVote;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

beforeEach(function () {
    config()->set('laravel-crm.modules', ['features']);
    FeatureStatus::firstOrCreate(['name' => 'New'], ['is_default' => true, 'order' => 1, 'color' => '#6c757d']);
    $this->actingAsUser(['crm_access' => 1]);
});

test('FeatureShow mounts with non-empty votesChart and viewsChart', function () {
    $feature = Feature::create(['title' => 'Charted', 'is_public' => true]);
    $voter = User::create(['name' => 'V1', 'email' => 'vc1@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);

    FeatureVote::create(['feature_id' => $feature->id, 'user_id' => $voter->id]);
    FeatureView::create(['feature_id' => $feature->id, 'viewed_at' => now()]);
    FeatureView::create(['feature_id' => $feature->id, 'viewed_at' => now()->subDay()]);

    $component = Livewire::test(FeatureShow::class, ['feature' => $feature]);

    $votes = $component->get('votesChart');
    $views = $component->get('viewsChart');

    expect($votes)->toBeArray()->not->toBeEmpty();
    expect($votes['type'])->toBe('bar');
    expect($votes['data']['datasets'])->toHaveCount(2);
    expect($votes['data']['datasets'][1]['type'])->toBe('line');

    expect($views)->toBeArray()->not->toBeEmpty();
    expect($views['type'])->toBe('bar');
    expect($views['data']['datasets'])->toHaveCount(2);
});

test('changing chartPeriod re-runs both builds and bucket count changes', function () {
    $feature = Feature::create(['title' => 'Bucketed', 'is_public' => true]);

    $component = Livewire::test(FeatureShow::class, ['feature' => $feature])
        ->set('chartPeriod', 'last_30_days');

    $thirtyDayVotesLabels = count($component->get('votesChart')['data']['labels']);
    $thirtyDayViewsLabels = count($component->get('viewsChart')['data']['labels']);

    $component->set('chartPeriod', 'last_7_days');

    $sevenDayVotesLabels = count($component->get('votesChart')['data']['labels']);
    $sevenDayViewsLabels = count($component->get('viewsChart')['data']['labels']);

    expect($sevenDayVotesLabels)->not->toBe($thirtyDayVotesLabels);
    expect($sevenDayViewsLabels)->not->toBe($thirtyDayViewsLabels);
});

test('buildVotesChart produces bar config with cumulative line dataset', function () {
    $feature = Feature::create(['title' => 'Cumulative', 'is_public' => true]);

    $u1 = User::create(['name' => 'V1', 'email' => 'cum1@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);
    $u2 = User::create(['name' => 'V2', 'email' => 'cum2@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);

    FeatureVote::create(['feature_id' => $feature->id, 'user_id' => $u1->id]);
    FeatureVote::create(['feature_id' => $feature->id, 'user_id' => $u2->id]);

    $chart = Livewire::test(FeatureShow::class, ['feature' => $feature])->get('votesChart');

    expect($chart['type'])->toBe('bar');
    expect($chart['data']['datasets'][0])->not->toHaveKey('type');
    expect($chart['data']['datasets'][1]['type'])->toBe('line');

    $cumulative = $chart['data']['datasets'][1]['data'];
    expect(end($cumulative))->toBe(2);
});

test('FeatureVoters paginates and most-recent voter is first', function () {
    $feature = Feature::create(['title' => 'Voters', 'is_public' => true]);

    $older = User::create(['name' => 'Older', 'email' => 'old@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);
    $newer = User::create(['name' => 'Newer', 'email' => 'new@example.com', 'password' => bcrypt('x'), 'crm_access' => 0]);

    FeatureVote::create(['feature_id' => $feature->id, 'user_id' => $older->id, 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);
    FeatureVote::create(['feature_id' => $feature->id, 'user_id' => $newer->id, 'created_at' => now(), 'updated_at' => now()]);

    $component = Livewire::test(FeatureVoters::class, ['feature' => $feature]);

    $component->assertSee('Newer');
    $component->assertSee('Older');
    $component->assertSeeInOrder(['Newer', 'Older']);
});
