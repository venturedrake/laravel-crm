<?php

use Illuminate\Support\Facades\Http;
use VentureDrake\LaravelCrm\Services\ClickSendService;

function clickSendService(): ClickSendService
{
    return app(ClickSendService::class);
}

function seedClickSendCredentials(): void
{
    app('laravel-crm.settings')->set('clicksend_username', 'testuser');
    app('laravel-crm.settings')->set('clicksend_api_key', 'testapikey');
    app('laravel-crm.settings')->set('clicksend_default_from', 'MyCRM');
    app('laravel-crm.settings')->forgetCache();
}

test('is not configured when no credentials set', function () {
    expect(clickSendService()->isConfigured())->toBeFalse();
});

test('is configured when credentials are set', function () {
    seedClickSendCredentials();

    expect(clickSendService()->isConfigured())->toBeTrue();
});

test('username returns null when not configured', function () {
    expect(clickSendService()->username())->toBeNull();
});

test('username returns stored value', function () {
    seedClickSendCredentials();

    expect(clickSendService()->username())->toBe('testuser');
});

test('default from returns stored value', function () {
    seedClickSendCredentials();

    expect(clickSendService()->defaultFrom())->toBe('MyCRM');
});

test('send sms returns not ok when not configured', function () {
    $result = clickSendService()->sendSms('+15550001234', 'Hello!');

    expect($result['ok'])->toBeFalse();
    expect($result['error'])->toContain('not configured');
});

test('send sms returns ok on success response', function () {
    seedClickSendCredentials();

    Http::fake([
        'rest.clicksend.com/v3/sms/send' => Http::response([
            'http_code' => 200,
            'response_code' => 'SUCCESS',
            'response_msg' => 'Here are your data.',
            'data' => ['messages' => [['status' => 'SUCCESS', 'message_id' => 'msg-abc-123', 'to' => '+15550001234']]],
        ], 200),
    ]);

    $result = clickSendService()->sendSms('+15550001234', 'Hello from CRM!');

    expect($result['ok'])->toBeTrue();
    expect($result['message_id'])->toBe('msg-abc-123');
    expect($result['status'])->toBe('SUCCESS');
    expect($result['error'])->toBeNull();
});

test('send sms returns not ok on api error', function () {
    seedClickSendCredentials();

    Http::fake([
        'rest.clicksend.com/v3/sms/send' => Http::response([
            'http_code' => 401, 'response_code' => 'AUTHENTICATION_FAILED', 'response_msg' => 'Authentication failed',
        ], 401),
    ]);

    $result = clickSendService()->sendSms('+15550001234', 'Test');

    expect($result['ok'])->toBeFalse();
    expect($result['error'])->not->toBeNull();
});

test('send sms includes from when provided', function () {
    seedClickSendCredentials();

    Http::fake([
        'rest.clicksend.com/v3/sms/send' => function ($request) {
            $body = $request->data();
            expect($body['messages'][0]['from'])->toBe('Brand');

            return Http::response(['data' => ['messages' => [['status' => 'SUCCESS', 'message_id' => 'x']]]], 200);
        },
    ]);

    $result = clickSendService()->sendSms('+15550001234', 'Hi', 'Brand');

    expect($result['ok'])->toBeTrue();
});

test('verify credentials returns not ok when not configured', function () {
    $result = clickSendService()->verifyCredentials();

    expect($result['ok'])->toBeFalse();
    expect($result['error'])->toContain('not configured');
});

test('verify credentials returns ok with balance on success', function () {
    seedClickSendCredentials();

    Http::fake([
        'rest.clicksend.com/v3/account' => Http::response(['http_code' => 200, 'data' => ['balance' => '42.50']], 200),
    ]);

    $result = clickSendService()->verifyCredentials();

    expect($result['ok'])->toBeTrue();
    expect($result['balance'])->toBe(42.5);
    expect($result['error'])->toBeNull();
});

test('verify credentials returns not ok on http error', function () {
    seedClickSendCredentials();

    Http::fake([
        'rest.clicksend.com/v3/account' => Http::response(['response_msg' => 'Unauthorized'], 401),
    ]);

    $result = clickSendService()->verifyCredentials();

    expect($result['ok'])->toBeFalse();
    expect($result['error'])->not->toBeNull();
});

test('refresh clears settings cache', function () {
    seedClickSendCredentials();

    $service = clickSendService();
    $service->username();

    app('laravel-crm.settings')->set('clicksend_username', 'newuser');
    $service->refresh();

    expect($service->username())->toBe('newuser');
});
