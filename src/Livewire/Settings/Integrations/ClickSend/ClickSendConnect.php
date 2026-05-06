<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Integrations\ClickSend;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Services\ClickSendService;

class ClickSendConnect extends Component
{
    use AuthorizesRequests;
    use Toast;

    /**
     * Plain-text inputs for new credentials. Only populated when the user is
     * entering or rotating values — never seeded with stored secrets, since
     * Livewire serialises public properties into the client snapshot.
     */
    public ?string $username_input = null;

    public ?string $api_key_input = null;

    public ?string $default_from = null;

    /**
     * Read-only previews of stored credentials, computed server-side and
     * exposed back to the client only as masks (e.g. "•••• 1234").
     */
    #[Locked]
    public bool $has_username = false;

    #[Locked]
    public ?string $username_mask = null;

    #[Locked]
    public bool $has_api_key = false;

    #[Locked]
    public ?string $api_key_mask = null;

    #[Locked]
    public bool $verified = false;

    #[Locked]
    public ?float $balance = null;

    #[Locked]
    public ?string $errorMessage = null;

    public function mount(ClickSendService $clickSend): void
    {
        $this->loadStoredState($clickSend);

        if ($clickSend->isConfigured()) {
            $this->checkBalance($clickSend);
        }
    }

    protected function rules(): array
    {
        return [
            'username_input' => $this->has_username ? 'nullable|string|max:255' : 'required|string|max:255',
            'api_key_input' => $this->has_api_key ? 'nullable|string|max:255' : 'required|string|max:255',
            'default_from' => 'nullable|string|max:32',
        ];
    }

    public function save(ClickSendService $clickSend): void
    {
        $this->authorize('update', Setting::class);

        $this->validate();

        if (filled($this->username_input)) {
            Setting::updateOrCreate(['name' => 'clicksend_username'], ['value' => $this->username_input]);
        }

        if (filled($this->api_key_input)) {
            Setting::updateOrCreate(['name' => 'clicksend_api_key'], ['value' => $this->api_key_input]);
        }

        Setting::updateOrCreate(['name' => 'clicksend_default_from'], ['value' => $this->default_from]);

        $this->username_input = null;
        $this->api_key_input = null;

        $clickSend->refresh();
        $this->loadStoredState($clickSend);
        $this->checkBalance($clickSend);

        if ($this->verified) {
            $this->success(ucfirst(__('laravel-crm::lang.clicksend')).' '.__('laravel-crm::lang.connected'));
        } else {
            $this->error($this->errorMessage ?? __('laravel-crm::lang.connection_failed'));
        }
    }

    public function disconnect(ClickSendService $clickSend): void
    {
        $this->authorize('update', Setting::class);

        Setting::where('name', 'clicksend_username')->delete();
        Setting::where('name', 'clicksend_api_key')->delete();
        Setting::where('name', 'clicksend_default_from')->delete();

        $this->username_input = null;
        $this->api_key_input = null;
        $this->default_from = null;
        $this->verified = false;
        $this->balance = null;
        $this->errorMessage = null;

        $clickSend->refresh();
        $this->loadStoredState($clickSend);

        $this->success(ucfirst(__('laravel-crm::lang.clicksend')).' '.__('laravel-crm::lang.disconnected'));
    }

    private function loadStoredState(ClickSendService $clickSend): void
    {
        $username = $clickSend->username();
        $apiKey = $clickSend->apiKey();

        $this->has_username = filled($username);
        $this->username_mask = $this->has_username ? $this->mask($username) : null;
        $this->has_api_key = filled($apiKey);
        $this->api_key_mask = $this->has_api_key ? $this->mask($apiKey) : null;
        $this->default_from = $clickSend->defaultFrom();
    }

    private function mask(?string $value): string
    {
        $value = (string) $value;

        if (mb_strlen($value) <= 4) {
            return str_repeat('•', max(4, mb_strlen($value)));
        }

        return str_repeat('•', 8).mb_substr($value, -4);
    }

    private function checkBalance(ClickSendService $clickSend): void
    {
        $result = $clickSend->verifyCredentials();
        $this->verified = $result['ok'];
        $this->balance = $result['balance'];
        $this->errorMessage = $result['error'];
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.integrations.clicksend.click-send-connect')
            ->layout('laravel-crm::layouts.app');
    }
}
