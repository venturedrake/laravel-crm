<?php

namespace VentureDrake\LaravelCrm\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ClickSendService
{
    private const BASE_URL = 'https://rest.clicksend.com/v3';

    private SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->username()) && ! empty($this->apiKey());
    }

    public function username(): ?string
    {
        return $this->settingService->get('clicksend_username') ?: null;
    }

    public function apiKey(): ?string
    {
        return $this->settingService->get('clicksend_api_key') ?: null;
    }

    public function defaultFrom(): ?string
    {
        return $this->settingService->get('clicksend_default_from') ?: null;
    }

    /**
     * Send a single SMS message.
     *
     * @return array{ok: bool, message_id: ?string, status: ?string, error: ?string}
     */
    public function sendSms(string $to, string $body, ?string $from = null, ?string $customString = null): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'message_id' => null,
                'status' => null,
                'error' => 'ClickSend credentials not configured',
            ];
        }

        $message = [
            'to' => $to,
            'body' => $body,
        ];

        if ($from !== null && $from !== '') {
            $message['from'] = $from;
        }

        if ($customString !== null && $customString !== '') {
            $message['custom_string'] = $customString;
        }

        $response = $this->request()->post(self::BASE_URL.'/sms/send', [
            'messages' => [$message],
        ]);

        return $this->parseSendResponse($response);
    }

    /**
     * Verify the configured credentials by hitting the account endpoint.
     *
     * @return array{ok: bool, balance: ?float, error: ?string}
     */
    public function verifyCredentials(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'balance' => null, 'error' => 'ClickSend credentials not configured'];
        }

        try {
            $response = $this->request()->get(self::BASE_URL.'/account');
        } catch (\Throwable $e) {
            return ['ok' => false, 'balance' => null, 'error' => $e->getMessage()];
        }

        if (! $response->successful()) {
            return [
                'ok' => false,
                'balance' => null,
                'error' => $response->json('response_msg') ?? 'HTTP '.$response->status(),
            ];
        }

        return [
            'ok' => true,
            'balance' => (float) ($response->json('data.balance') ?? 0),
            'error' => null,
        ];
    }

    private function request()
    {
        return Http::withBasicAuth((string) $this->username(), (string) $this->apiKey())
            ->acceptJson()
            ->asJson()
            ->timeout(15);
    }

    private function parseSendResponse(Response $response): array
    {
        if (! $response->successful()) {
            return [
                'ok' => false,
                'message_id' => null,
                'status' => null,
                'error' => $response->json('response_msg') ?? 'HTTP '.$response->status(),
            ];
        }

        $first = $response->json('data.messages.0');

        if (! is_array($first)) {
            return [
                'ok' => false,
                'message_id' => null,
                'status' => null,
                'error' => 'Unexpected ClickSend response',
            ];
        }

        $status = $first['status'] ?? null;
        $messageId = $first['message_id'] ?? null;

        $ok = is_string($status) && in_array(strtoupper($status), ['SUCCESS', 'QUEUED', 'SENT'], true);

        return [
            'ok' => $ok,
            'message_id' => $messageId,
            'status' => $status,
            'error' => $ok ? null : ($first['error_text'] ?? $first['status'] ?? 'Unknown error'),
        ];
    }
}
