<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Integrations\ClickSend;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Services\ClickSendService;

class ClickSendConnect extends Component
{
    use Toast;

    public ?string $username = null;

    public ?string $api_key = null;

    public ?string $default_from = null;

    public bool $verified = false;

    public ?float $balance = null;

    public ?string $errorMessage = null;

    public function mount(ClickSendService $clickSend): void
    {
        $this->username = $clickSend->username();
        $this->api_key = $clickSend->apiKey();
        $this->default_from = $clickSend->defaultFrom();

        if ($clickSend->isConfigured()) {
            $this->checkBalance($clickSend);
        }
    }

    protected function rules(): array
    {
        return [
            'username' => 'required|string|max:255',
            'api_key' => 'required|string|max:255',
            'default_from' => 'nullable|string|max:32',
        ];
    }

    public function save(ClickSendService $clickSend): void
    {
        $this->validate();

        Setting::updateOrCreate(['name' => 'clicksend_username'], ['value' => $this->username]);
        Setting::updateOrCreate(['name' => 'clicksend_api_key'], ['value' => $this->api_key]);
        Setting::updateOrCreate(['name' => 'clicksend_default_from'], ['value' => $this->default_from]);

        $this->checkBalance($clickSend);

        if ($this->verified) {
            $this->success(ucfirst(__('laravel-crm::lang.clicksend')).' '.__('laravel-crm::lang.connected'));
        } else {
            $this->error($this->errorMessage ?? __('laravel-crm::lang.connection_failed'));
        }
    }

    public function disconnect(): void
    {
        Setting::where('name', 'clicksend_username')->delete();
        Setting::where('name', 'clicksend_api_key')->delete();
        Setting::where('name', 'clicksend_default_from')->delete();

        $this->username = null;
        $this->api_key = null;
        $this->default_from = null;
        $this->verified = false;
        $this->balance = null;
        $this->errorMessage = null;

        $this->success(ucfirst(__('laravel-crm::lang.clicksend')).' '.__('laravel-crm::lang.disconnected'));
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
