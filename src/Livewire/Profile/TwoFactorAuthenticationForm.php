<?php

namespace VentureDrake\LaravelCrm\Livewire\Profile;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Component;
use Mary\Traits\Toast;
use PragmaRX\Google2FA\Google2FA;

/**
 * Two-Factor Authentication management modeled on Laravel Jetstream / Fortify.
 * Requires `pragmarx/google2fa` (and optionally `bacon/bacon-qr-code`) to be installed
 * in the host application. Section is hidden by the parent view if Google2FA is not available.
 */
class TwoFactorAuthenticationForm extends Component
{
    use Toast;

    public $showingQrCode = false;

    public $showingRecoveryCodes = false;

    public $confirming = false;

    public $code = '';

    public function getEnabledProperty(): bool
    {
        $user = auth()->user();

        return ! is_null($user->two_factor_secret ?? null)
            && (
                ! Schema::hasColumn($user->getTable(), 'two_factor_confirmed_at')
                || ! is_null($user->two_factor_confirmed_at)
            );
    }

    public function getPendingProperty(): bool
    {
        $user = auth()->user();

        return ! is_null($user->two_factor_secret ?? null)
            && Schema::hasColumn($user->getTable(), 'two_factor_confirmed_at')
            && is_null($user->two_factor_confirmed_at);
    }

    public function enable()
    {
        $user = auth()->user();

        $google2fa = $this->google2fa();

        $user->forceFill([
            'two_factor_secret' => encrypt($google2fa->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode(Collection::times(8, fn () => $this->generateRecoveryCode())->all())),
        ]);

        if (Schema::hasColumn($user->getTable(), 'two_factor_confirmed_at')) {
            $user->two_factor_confirmed_at = null;
        }

        $user->save();

        $this->showingQrCode = true;
        $this->confirming = Schema::hasColumn($user->getTable(), 'two_factor_confirmed_at');
        $this->showingRecoveryCodes = ! $this->confirming;
    }

    public function confirm()
    {
        $this->validate(['code' => ['required', 'string']]);

        $user = auth()->user();

        $valid = $this->google2fa()->verifyKey(
            decrypt($user->two_factor_secret),
            $this->code
        );

        if (! $valid) {
            $this->addError('code', __('The provided two factor authentication code was invalid.'));

            return;
        }

        if (Schema::hasColumn($user->getTable(), 'two_factor_confirmed_at')) {
            $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        }

        $this->confirming = false;
        $this->code = '';
        $this->showingRecoveryCodes = true;

        $this->success(ucfirst(__('laravel-crm::lang.two_factor_enabled')));
    }

    public function regenerateRecoveryCodes()
    {
        $user = auth()->user();

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode(Collection::times(8, fn () => $this->generateRecoveryCode())->all())),
        ])->save();

        $this->showingRecoveryCodes = true;
    }

    public function showRecoveryCodes()
    {
        $this->showingRecoveryCodes = true;
    }

    public function disable()
    {
        $user = auth()->user();

        $fields = ['two_factor_secret' => null, 'two_factor_recovery_codes' => null];

        if (Schema::hasColumn($user->getTable(), 'two_factor_confirmed_at')) {
            $fields['two_factor_confirmed_at'] = null;
        }

        $user->forceFill($fields)->save();

        $this->showingQrCode = false;
        $this->showingRecoveryCodes = false;
        $this->confirming = false;

        $this->success(ucfirst(__('laravel-crm::lang.two_factor_disabled')));
    }

    public function getQrCodeSvgProperty(): ?string
    {
        $user = auth()->user();

        if (! $user->two_factor_secret || ! class_exists(Writer::class)) {
            return null;
        }

        $url = $this->google2fa()->getQRCodeUrl(
            config('app.name'),
            $user->email,
            decrypt($user->two_factor_secret)
        );

        $renderer = new ImageRenderer(
            new RendererStyle(192, 0),
            new SvgImageBackEnd
        );

        return (new Writer($renderer))->writeString($url);
    }

    public function getSetupKeyProperty(): ?string
    {
        $user = auth()->user();

        return $user->two_factor_secret ? decrypt($user->two_factor_secret) : null;
    }

    public function getRecoveryCodesProperty(): array
    {
        $user = auth()->user();

        if (! $user->two_factor_recovery_codes) {
            return [];
        }

        return json_decode(decrypt($user->two_factor_recovery_codes), true) ?: [];
    }

    protected function google2fa()
    {
        return app(Google2FA::class);
    }

    protected function generateRecoveryCode(): string
    {
        return Str::random(10).'-'.Str::random(10);
    }

    public function render()
    {
        return view('laravel-crm::livewire.profile.two-factor-authentication-form');
    }
}
