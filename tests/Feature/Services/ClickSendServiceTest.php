<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Services;

use Illuminate\Support\Facades\Http;
use VentureDrake\LaravelCrm\Services\ClickSendService;
use VentureDrake\LaravelCrm\Tests\TestCase;

class ClickSendServiceTest extends TestCase
{
    private function service(): ClickSendService
    {
        return app(ClickSendService::class);
    }

    private function seedCredentials(): void
    {
        app('laravel-crm.settings')->set('clicksend_username', 'testuser');
        app('laravel-crm.settings')->set('clicksend_api_key', 'testapikey');
        app('laravel-crm.settings')->set('clicksend_default_from', 'MyCRM');
        app('laravel-crm.settings')->forgetCache();
    }

    public function test_is_not_configured_when_no_credentials_set(): void
    {
        $this->assertFalse($this->service()->isConfigured());
    }

    public function test_is_configured_when_credentials_are_set(): void
    {
        $this->seedCredentials();

        $this->assertTrue($this->service()->isConfigured());
    }

    public function test_username_returns_null_when_not_configured(): void
    {
        $this->assertNull($this->service()->username());
    }

    public function test_username_returns_stored_value(): void
    {
        $this->seedCredentials();

        $this->assertSame('testuser', $this->service()->username());
    }

    public function test_default_from_returns_stored_value(): void
    {
        $this->seedCredentials();

        $this->assertSame('MyCRM', $this->service()->defaultFrom());
    }

    public function test_send_sms_returns_not_ok_when_not_configured(): void
    {
        $result = $this->service()->sendSms('+15550001234', 'Hello!');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('not configured', $result['error']);
    }

    public function test_send_sms_returns_ok_on_success_response(): void
    {
        $this->seedCredentials();

        Http::fake([
            'rest.clicksend.com/v3/sms/send' => Http::response([
                'http_code' => 200,
                'response_code' => 'SUCCESS',
                'response_msg' => 'Here are your data.',
                'data' => [
                    'messages' => [
                        [
                            'status' => 'SUCCESS',
                            'message_id' => 'msg-abc-123',
                            'to' => '+15550001234',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service()->sendSms('+15550001234', 'Hello from CRM!');

        $this->assertTrue($result['ok']);
        $this->assertSame('msg-abc-123', $result['message_id']);
        $this->assertSame('SUCCESS', $result['status']);
        $this->assertNull($result['error']);
    }

    public function test_send_sms_returns_not_ok_on_api_error(): void
    {
        $this->seedCredentials();

        Http::fake([
            'rest.clicksend.com/v3/sms/send' => Http::response([
                'http_code' => 401,
                'response_code' => 'AUTHENTICATION_FAILED',
                'response_msg' => 'Authentication failed',
            ], 401),
        ]);

        $result = $this->service()->sendSms('+15550001234', 'Test');

        $this->assertFalse($result['ok']);
        $this->assertNotNull($result['error']);
    }

    public function test_send_sms_includes_from_when_provided(): void
    {
        $this->seedCredentials();

        Http::fake([
            'rest.clicksend.com/v3/sms/send' => function ($request) {
                $body = $request->data();
                $this->assertSame('Brand', $body['messages'][0]['from']);

                return Http::response([
                    'data' => ['messages' => [['status' => 'SUCCESS', 'message_id' => 'x']]],
                ], 200);
            },
        ]);

        $result = $this->service()->sendSms('+15550001234', 'Hi', 'Brand');

        $this->assertTrue($result['ok']);
    }

    public function test_verify_credentials_returns_not_ok_when_not_configured(): void
    {
        $result = $this->service()->verifyCredentials();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('not configured', $result['error']);
    }

    public function test_verify_credentials_returns_ok_with_balance_on_success(): void
    {
        $this->seedCredentials();

        Http::fake([
            'rest.clicksend.com/v3/account' => Http::response([
                'http_code' => 200,
                'data' => ['balance' => '42.50'],
            ], 200),
        ]);

        $result = $this->service()->verifyCredentials();

        $this->assertTrue($result['ok']);
        $this->assertSame(42.5, $result['balance']);
        $this->assertNull($result['error']);
    }

    public function test_verify_credentials_returns_not_ok_on_http_error(): void
    {
        $this->seedCredentials();

        Http::fake([
            'rest.clicksend.com/v3/account' => Http::response([
                'response_msg' => 'Unauthorized',
            ], 401),
        ]);

        $result = $this->service()->verifyCredentials();

        $this->assertFalse($result['ok']);
        $this->assertNotNull($result['error']);
    }

    public function test_refresh_clears_settings_cache(): void
    {
        $this->seedCredentials();

        // Prime the cache
        $service = $this->service();
        $service->username(); // loads cache

        // Clear and change credentials
        app('laravel-crm.settings')->set('clicksend_username', 'newuser');
        $service->refresh();

        $this->assertSame('newuser', $service->username());
    }
}
