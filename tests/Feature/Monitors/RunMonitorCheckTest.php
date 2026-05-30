<?php

use Carbon\Carbon;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response as Psr7Response;
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

test('response_time_ms comes from Guzzle on_stats transfer time, not wall-clock', function () {
    Notification::fake();

    $wallClockDelayMicros = 200_000; // ~200ms wall-clock delay inside the handler
    $stubbedTransferSeconds = 0.01;  // ~10ms reported by on_stats

    // Real Guzzle MockHandler — its invokeStats() builds a TransferStats with
    // the transferTime taken from $options['transfer_time'] and invokes
    // Laravel's wrapped on_stats callback. The callable response delays via
    // usleep so wall-clock elapsed is provably distinct from the stubbed value.
    $mockHandler = new MockHandler([
        function ($request, $options) use ($wallClockDelayMicros) {
            usleep($wallClockDelayMicros);

            return new Psr7Response(200, [], 'OK');
        },
    ]);

    // Push the MockHandler in via globalMiddleware so it short-circuits the
    // PendingRequest's handler stack before the default cURL handler runs.
    // Setting transfer_time here makes MockHandler hand on_stats a known value.
    Http::globalMiddleware(function (callable $handler) use ($mockHandler, $stubbedTransferSeconds) {
        return function ($request, $options) use ($mockHandler, $stubbedTransferSeconds) {
            $options['transfer_time'] = $stubbedTransferSeconds;

            return $mockHandler($request, $options);
        };
    });

    $monitor = makeMonitor();

    $service = app(MonitorCheckService::class);

    $wallStart = microtime(true);
    $result = $service->checkUptime($monitor);
    $wallElapsedMs = (int) round((microtime(true) - $wallStart) * 1000);

    expect($result['status'])->toBe('up');
    expect($result['status_code'])->toBe(200);

    // Wall-clock must clearly be in the delay range so the test is a real
    // discriminator: if checkUptime() reverted to wall-clock measurement, the
    // following response_time_ms assertions would fail.
    expect($wallElapsedMs)->toBeGreaterThanOrEqual(150);

    // response_time_ms must reflect the on_stats transfer time (≈10ms), with
    // a small tolerance to absorb integer rounding.
    expect($result['response_time_ms'])->not->toBeNull();
    expect($result['response_time_ms'])->toBeGreaterThanOrEqual(8);
    expect($result['response_time_ms'])->toBeLessThanOrEqual(15);

    // And it must be clearly distinct from wall-clock (≥100ms gap).
    expect($wallElapsedMs - $result['response_time_ms'])->toBeGreaterThanOrEqual(100);
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
