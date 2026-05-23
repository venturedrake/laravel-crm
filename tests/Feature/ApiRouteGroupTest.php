<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use VentureDrake\LaravelCrm\Http\Middleware\ForceJsonResponse;
use VentureDrake\LaravelCrm\Http\Middleware\SetApiTeamContext;

test('middleware aliases are registered', function () {
    $aliases = app('router')->getMiddleware();

    expect($aliases)->toHaveKey('laravel-crm.api.json');
    expect($aliases)->toHaveKey('laravel-crm.api.team');
    expect($aliases['laravel-crm.api.json'])->toBe(ForceJsonResponse::class);
    expect($aliases['laravel-crm.api.team'])->toBe(SetApiTeamContext::class);
});

test('the laravel-crm-api rate limiter is registered', function () {
    $resolver = RateLimiter::limiter('laravel-crm-api');

    expect($resolver)->not->toBeNull();

    $unauthRequest = Request::create('/crm/api/v2/leads');
    $unauthRequest->setUserResolver(fn () => null);

    $authRequest = Request::create('/crm/api/v2/leads');
    $authRequest->setUserResolver(fn () => new class
    {
        public function getAuthIdentifier()
        {
            return 42;
        }
    });

    /** @var Limit $unauth */
    $unauth = $resolver($unauthRequest);
    /** @var Limit $auth */
    $auth = $resolver($authRequest);

    expect($unauth->maxAttempts)->toBe(30);
    expect($auth->maxAttempts)->toBe(60);
});

test('the crm/api/v2 route group is registered and reachable', function () {
    Route::middleware(['api', 'laravel-crm.api.json', 'throttle:laravel-crm-api'])
        ->prefix('crm/api/v2')
        ->get('__group-probe', fn (Request $request) => response()->json([
            'accept' => $request->headers->get('Accept'),
        ]));

    $response = $this->get('/crm/api/v2/__group-probe');

    $response->assertOk();
    $response->assertJson(['accept' => 'application/json']);
});

test('an empty group still resolves under the crm/api/v2 prefix without routes', function () {
    $response = $this->get('/crm/api/v2/does-not-exist');

    $response->assertStatus(404);
});
