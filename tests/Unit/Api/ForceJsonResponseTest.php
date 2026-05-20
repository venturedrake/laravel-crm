<?php

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Http\Middleware\ForceJsonResponse;

test('it forces the Accept header to application/json', function () {
    $middleware = new ForceJsonResponse;
    $request = Request::create('/api/crm/v2/whatever');
    $request->headers->set('Accept', 'text/html');

    $middleware->handle($request, function ($passed) use (&$captured) {
        $captured = $passed;

        return response('ok');
    });

    expect($captured->headers->get('Accept'))->toBe('application/json');
});

test('it sets Accept header when not previously set', function () {
    $middleware = new ForceJsonResponse;
    $request = Request::create('/api/crm/v2/whatever');

    $middleware->handle($request, function ($passed) use (&$captured) {
        $captured = $passed;

        return response('ok');
    });

    expect($captured->headers->get('Accept'))->toBe('application/json');
});
