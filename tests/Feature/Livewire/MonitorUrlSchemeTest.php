<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Monitors\MonitorCreate;
use VentureDrake\LaravelCrm\Livewire\Monitors\MonitorEdit;
use VentureDrake\LaravelCrm\Models\Monitor;
use VentureDrake\LaravelCrm\Services\MonitorService;

/**
 * Render-stub subclasses -- see NoteAuthorizationTest for the rationale. Only render()
 * is replaced; validation and save() run for real.
 */
class SchemeMonitorCreate extends MonitorCreate
{
    public function render()
    {
        return '<div></div>';
    }
}
class SchemeMonitorEdit extends MonitorEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

beforeEach(function () {
    $this->actingAsUserWithPermissions(['view crm monitors', 'create crm monitors', 'edit crm monitors']);
    // Hostname cases use non-resolving test domains; the guard's DNS step is
    // exercised separately below with private targets disallowed.
    config()->set('laravel-crm.monitoring.allow_private_targets', true);
});

function schemeMonitor(array $attributes = []): Monitor
{
    return Monitor::create(array_merge([
        'user_owner_id' => auth()->id(),
        'name' => 'Original monitor',
        'type' => 'https',
        'url' => 'https://original.example.test',
        'host' => 'original.example.test',
        'method' => 'GET',
        'expected_status_code' => 200,
        'interval' => 5,
        'downtime_minutes_before_alert' => 5,
        'perf_threshold_ms' => 3500,
        'is_active' => true,
    ], $attributes));
}

/*
 * ---------------------------------------------------------------------------
 * MonitorCreate
 * ---------------------------------------------------------------------------
 */

it('accepts a bare hostname and prepends the selected https scheme', function () {
    Livewire::test(SchemeMonitorCreate::class)
        ->set('name', 'Bare host')
        ->set('type', 'https')
        ->set('url', 'example.com')
        ->call('save')
        ->assertHasNoErrors();

    $monitor = Monitor::where('name', 'Bare host')->firstOrFail();

    expect($monitor->url)->toBe('https://example.com')
        ->and($monitor->type)->toBe('https')
        ->and($monitor->host)->toBe('example.com');
});

it('prepends the selected http scheme and keeps the path', function () {
    Livewire::test(SchemeMonitorCreate::class)
        ->set('name', 'Http path')
        ->set('type', 'http')
        ->set('url', 'example.com/health')
        ->call('save')
        ->assertHasNoErrors();

    $monitor = Monitor::where('name', 'Http path')->firstOrFail();

    expect($monitor->url)->toBe('http://example.com/health')
        ->and($monitor->type)->toBe('http');
});

it('takes the scheme from a pasted full url so type and url never disagree', function () {
    Livewire::test(SchemeMonitorCreate::class)
        ->set('name', 'Pasted url')
        ->set('type', 'http')
        ->set('url', 'https://example.com')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('type', 'https')
        ->assertSet('url', 'example.com');

    $monitor = Monitor::where('name', 'Pasted url')->firstOrFail();

    expect($monitor->url)->toBe('https://example.com')
        ->and($monitor->type)->toBe('https');
});

it('rejects a non-http scheme', function () {
    $before = Monitor::count();

    Livewire::test(SchemeMonitorCreate::class)
        ->set('url', 'ftp://example.com')
        ->call('save')
        ->assertHasErrors(['url']);

    expect(Monitor::count())->toBe($before);
});

it('rejects a scheme selector value outside http and https', function () {
    Livewire::test(SchemeMonitorCreate::class)
        ->set('type', 'tcp')
        ->set('url', 'example.com')
        ->call('save')
        ->assertHasErrors(['type', 'url']);
});

it('still enforces the SSRF guard on bare hostnames', function (string $host) {
    config()->set('laravel-crm.monitoring.allow_private_targets', false);
    $before = Monitor::count();

    Livewire::test(SchemeMonitorCreate::class)
        ->set('type', 'https')
        ->set('url', $host)
        ->call('save')
        ->assertHasErrors(['url']);

    expect(Monitor::count())->toBe($before);
})->with([
    'loopback' => '127.0.0.1',
    'cloud metadata' => '169.254.169.254',
]);

/*
 * ---------------------------------------------------------------------------
 * MonitorEdit
 * ---------------------------------------------------------------------------
 */

it('splits the stored url into the scheme selector and hostname on mount', function () {
    $monitor = schemeMonitor();

    Livewire::test(SchemeMonitorEdit::class, ['monitor' => $monitor])
        ->assertSet('type', 'https')
        ->assertSet('url', 'original.example.test');
});

it('lets the stored url scheme win over a disagreeing stored type on mount', function () {
    $monitor = schemeMonitor(['type' => 'http']);

    Livewire::test(SchemeMonitorEdit::class, ['monitor' => $monitor])
        ->assertSet('type', 'https')
        ->assertSet('url', 'original.example.test');
});

it('keeps the stored url and down state when saved unchanged', function () {
    $monitor = schemeMonitor(['down_since_at' => now()->subHour()]);

    Livewire::test(SchemeMonitorEdit::class, ['monitor' => $monitor])
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $monitor->fresh();

    expect($fresh->url)->toBe('https://original.example.test')
        ->and($fresh->type)->toBe('https')
        ->and($fresh->down_since_at)->not->toBeNull();
});

it('switches the stored url scheme when the selector changes', function () {
    $monitor = schemeMonitor();

    Livewire::test(SchemeMonitorEdit::class, ['monitor' => $monitor])
        ->set('type', 'http')
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $monitor->fresh();

    expect($fresh->url)->toBe('http://original.example.test')
        ->and($fresh->type)->toBe('http');
});

/*
 * ---------------------------------------------------------------------------
 * MonitorService
 * ---------------------------------------------------------------------------
 */

it('stores the type from the url scheme when the service is given a contradicting type', function () {
    $monitor = app(MonitorService::class)->create([
        'name' => 'Service monitor',
        'type' => 'https',
        'url' => 'http://x.test',
        'method' => 'GET',
        'expected_status_code' => 200,
        'interval' => 5,
        'downtime_minutes_before_alert' => 5,
        'perf_threshold_ms' => 3500,
        'is_active' => true,
    ]);

    expect($monitor->fresh()->type)->toBe('http')
        ->and($monitor->fresh()->url)->toBe('http://x.test');
});

it('syncs the type with the url scheme on service update', function () {
    $monitor = schemeMonitor();

    app(MonitorService::class)->update($monitor, ['url' => 'http://other.example.test', 'type' => 'https']);

    expect($monitor->fresh()->type)->toBe('http');
});
