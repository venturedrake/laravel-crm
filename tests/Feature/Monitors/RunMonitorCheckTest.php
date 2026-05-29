<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use VentureDrake\LaravelCrm\Jobs\RunMonitorCheck;
use VentureDrake\LaravelCrm\Models\Monitor;
use VentureDrake\LaravelCrm\Models\MonitorCheck;
use VentureDrake\LaravelCrm\Notifications\MonitorDownNotification;
use VentureDrake\LaravelCrm\Notifications\MonitorPerformanceNotification;
use VentureDrake\LaravelCrm\Notifications\MonitorRecoveredNotification;
use VentureDrake\LaravelCrm\Services\MonitorCheckService;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function makeMonitorOwner(): User
{
    return User::create([
        'name' => 'Monitor Owner',
        'email' => 'owner'.uniqid().'@example.com',
        'password' => bcrypt('secret'),
    ]);
}

function makeMonitor(array $overrides = []): Monitor
{
    $owner = $overrides['owner'] ?? makeMonitorOwner();
    unset($overrides['owner']);

    return Monitor::create(array_merge([
        'name' => 'Acme Site',
        'url' => 'https://acme.test',
        'method' => 'GET',
        'is_active' => true,
        'uptime_enabled' => true,
        'ssl_enabled' => false,
        'user_owner_id' => $owner->id,
    ], $overrides));
}

test('uptime check records an up MonitorCheck row and updates last_checked_at on a 200 response', function () {
    Notification::fake();
    Http::fake([
        '*' => Http::response('OK', 200),
    ]);

    $monitor = makeMonitor();

    expect($monitor->last_checked_at)->toBeNull();

    (new RunMonitorCheck($monitor->id))->handle(app(MonitorCheckService::class));

    $monitor->refresh();

    expect($monitor->last_status)->toBe('up');
    expect($monitor->last_status_code)->toBe(200);
    expect($monitor->last_checked_at)->not->toBeNull();

    $row = MonitorCheck::where('monitor_id', $monitor->id)->where('type', 'uptime')->first();

    expect($row)->not->toBeNull();
    expect($row->status)->toBe('up');
    expect($row->status_code)->toBe(200);

    Notification::assertNothingSent();
});

test('down notification is not sent within the debounce window', function () {
    Notification::fake();
    Http::fake([
        '*' => Http::response(null, 503),
    ]);

    $owner = makeMonitorOwner();
    $monitor = makeMonitor([
        'owner' => $owner,
        'downtime_minutes_before_alert' => 5,
    ]);

    (new RunMonitorCheck($monitor->id))->handle(app(MonitorCheckService::class));

    $monitor->refresh();

    expect($monitor->last_status)->toBe('down');
    expect($monitor->down_since_at)->not->toBeNull();
    expect($monitor->notified_at)->toBeNull();

    Notification::assertNothingSent();
});

test('down notification fires only after downtime_minutes_before_alert is exceeded', function () {
    Notification::fake();
    Http::fake([
        '*' => Http::response(null, 503),
    ]);

    $owner = makeMonitorOwner();
    $monitor = makeMonitor([
        'owner' => $owner,
        'downtime_minutes_before_alert' => 2,
        'last_status' => 'down',
        'down_since_at' => Carbon::now()->subMinutes(10),
    ]);

    (new RunMonitorCheck($monitor->id))->handle(app(MonitorCheckService::class));

    $monitor->refresh();

    expect($monitor->last_status)->toBe('down');
    expect($monitor->notified_at)->not->toBeNull();

    Notification::assertSentTo($owner, MonitorDownNotification::class);
});

test('recovery from down state dispatches MonitorRecoveredNotification', function () {
    Notification::fake();
    Http::fake([
        '*' => Http::response('OK', 200),
    ]);

    $owner = makeMonitorOwner();
    $monitor = makeMonitor([
        'owner' => $owner,
        'last_status' => 'down',
        'down_since_at' => Carbon::now()->subMinutes(20),
        'notified_at' => Carbon::now()->subMinutes(15),
    ]);

    (new RunMonitorCheck($monitor->id))->handle(app(MonitorCheckService::class));

    $monitor->refresh();

    expect($monitor->last_status)->toBe('up');
    expect($monitor->down_since_at)->toBeNull();
    expect($monitor->notified_at)->toBeNull();

    Notification::assertSentTo($owner, MonitorRecoveredNotification::class);
});

test('recovery does not notify when no down notification was sent', function () {
    Notification::fake();
    Http::fake([
        '*' => Http::response('OK', 200),
    ]);

    $owner = makeMonitorOwner();
    $monitor = makeMonitor([
        'owner' => $owner,
        'last_status' => 'down',
        'down_since_at' => Carbon::now()->subSeconds(30),
        'notified_at' => null,
    ]);

    (new RunMonitorCheck($monitor->id))->handle(app(MonitorCheckService::class));

    $monitor->refresh();

    expect($monitor->last_status)->toBe('up');
    expect($monitor->down_since_at)->toBeNull();

    Notification::assertNothingSent();
});

test('perf alert fires when response_time_ms exceeds perf_threshold_ms', function () {
    Notification::fake();

    $owner = makeMonitorOwner();
    $monitor = makeMonitor([
        'owner' => $owner,
        'perf_threshold_ms' => 500,
    ]);

    $stub = new class extends MonitorCheckService
    {
        public function checkUptime(Monitor $monitor): array
        {
            return [
                'status' => 'slow',
                'status_code' => 200,
                'response_time_ms' => 1500,
                'error' => null,
            ];
        }
    };

    (new RunMonitorCheck($monitor->id))->handle($stub);

    $monitor->refresh();

    expect($monitor->last_status)->toBe('slow');
    expect($monitor->last_response_time)->toBe(1500);
    expect($monitor->notified_at)->not->toBeNull();

    Notification::assertSentTo($owner, MonitorPerformanceNotification::class);
});

test('uptime check rejected by SSRF guard records null response_time and issues no HTTP request', function () {
    Notification::fake();
    Http::fake();

    config()->set('laravel-crm.monitoring.allow_private_targets', false);

    $monitor = makeMonitor([
        'url' => 'http://127.0.0.1/',
    ]);

    $service = app(MonitorCheckService::class);

    $result = $service->checkUptime($monitor);

    expect($result['response_time_ms'])->toBeNull();
    expect($result['status'])->toBe('down');
    expect($result['error'])->toBeString()->not->toBeEmpty();
    expect($result['error'])->toBe('URL host resolves to a non-routable address.');

    Http::assertNothingSent();

    (new RunMonitorCheck($monitor->id))->handle($service);

    $row = MonitorCheck::where('monitor_id', $monitor->id)->where('type', 'uptime')->first();

    expect($row)->not->toBeNull();
    expect($row->response_time)->toBeNull();
    expect($row->status)->toBe('down');
    expect($row->error_message)->toBe('URL host resolves to a non-routable address.');

    Http::assertNothingSent();
});

test('SSL check is skipped when ssl_last_checked_at is within ssl_recheck_hours', function () {
    Notification::fake();
    Http::fake([
        '*' => Http::response('OK', 200),
    ]);

    config()->set('laravel-crm.monitoring.ssl_recheck_hours', 12);

    $owner = makeMonitorOwner();
    $monitor = makeMonitor([
        'owner' => $owner,
        'ssl_enabled' => true,
        'ssl_last_checked_at' => Carbon::now()->subHours(2),
        'ssl_status' => 'valid',
    ]);

    $sslSpy = new class extends MonitorCheckService
    {
        public bool $sslCalled = false;

        public function checkSsl(Monitor $monitor): array
        {
            $this->sslCalled = true;

            return [
                'valid' => true,
                'issuer' => 'Test CA',
                'expires_at' => Carbon::now()->addDays(60),
                'error' => null,
            ];
        }
    };

    (new RunMonitorCheck($monitor->id))->handle($sslSpy);

    expect($sslSpy->sslCalled)->toBeFalse();

    $sslChecks = MonitorCheck::where('monitor_id', $monitor->id)->where('type', 'ssl')->count();

    expect($sslChecks)->toBe(0);
});
