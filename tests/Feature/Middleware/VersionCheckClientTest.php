<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use VentureDrake\LaravelCrm\LaravelCrmServiceProvider;
use VentureDrake\LaravelCrm\Models\Setting;

/*
 * The version phone-home in the Settings middleware and UpdateController must
 * take its Guzzle client from the package's own `laravel-crm.version-client`
 * binding. TestCase binds an offline client there; if either call site goes
 * back to `new Client`, every HTTP test without an install_id row starts
 * hitting api.laravelcrm.com again. And it must not be `app(Client::class)`,
 * which would pick up whatever Guzzle client the host app has bound — default
 * auth headers included — and send it to our API.
 */

function bindRecordingClient(array &$history): void
{
    $stack = HandlerStack::create(new MockHandler([
        new Response(200, [], json_encode(['id' => 'install-from-api', 'version' => '9.9.9'])),
    ]));
    $stack->push(Middleware::history($history));

    app()->bind('laravel-crm.version-client', fn () => new Client(['handler' => $stack]));
}

test('the settings middleware resolves its version-check client from the package binding', function () {
    $history = [];
    bindRecordingClient($history);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['view crm activities'])]);

    $this->get(route('laravel-crm.activities.index'))->assertOk();

    expect($history)->toHaveCount(1)
        ->and((string) $history[0]['request']->getUri())->toBe('https://api.laravelcrm.com/api/v2/public/version')
        ->and(Setting::where('name', 'install_id')->value('value'))->toBe('install-from-api')
        ->and(Setting::where('name', 'version_latest')->value('value'))->toBe('9.9.9');
});

test('the updates page resolves its version-check client from the package binding', function () {
    // Stops the middleware's own check so the request below is the controller's.
    Cache::put('crm.version-check-attempted', true);

    $history = [];
    bindRecordingClient($history);

    $this->actingAsUserWithPermissions(['view crm updates']);

    $this->get(route('laravel-crm.updates.index'))->assertOk();

    expect($history)->toHaveCount(1)
        ->and(Setting::where('name', 'version_latest')->value('value'))->toBe('9.9.9');
});

test('the package binding does not hand out a Guzzle client the host app has bound', function () {
    // A host binding its own client, e.g. one carrying an API token for some
    // other service. The phone-home must not go through it.
    $hostStack = HandlerStack::create(new MockHandler);
    app()->bind(Client::class, fn () => new Client([
        'handler' => $hostStack,
        'headers' => ['Authorization' => 'Bearer host-secret'],
    ]));

    // TestCase replaces the package binding with an offline client; put the
    // provider's real one back so this exercises what ships. The client is
    // only inspected, never sent, so nothing reaches the network.
    (new LaravelCrmServiceProvider(app()))->register();

    $client = app('laravel-crm.version-client');

    expect($client)->toBeInstanceOf(Client::class)
        ->and($client->getConfig('handler'))->not->toBe($hostStack)
        ->and($client->getConfig('headers'))->not->toHaveKey('Authorization');
});
