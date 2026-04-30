<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.two_factor_authentication')) }}"
                 subtitle="{{ __('laravel-crm::lang.two_factor_subtitle') }}" separator>

        @if ($this->enabled)
            <h3 class="font-semibold mb-2">{{ __('laravel-crm::lang.two_factor_enabled_heading') }}</h3>
            <p class="text-sm mb-4">{{ __('laravel-crm::lang.two_factor_enabled_text') }}</p>

            @if ($showingRecoveryCodes && count($this->recoveryCodes))
                <div class="mb-4">
                    <p class="text-sm mb-2">{{ __('laravel-crm::lang.recovery_codes_text') }}</p>
                    <div class="grid gap-1 px-4 py-3 font-mono text-sm bg-base-200 rounded-md">
                        @foreach ($this->recoveryCodes as $code)
                            <div>{{ $code }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex gap-2 flex-wrap">
                @if ($showingRecoveryCodes)
                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.regenerate_recovery_codes')) }}"
                                   class="btn-sm" wire:click="regenerateRecoveryCodes" />
                @else
                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.show_recovery_codes')) }}"
                                   class="btn-sm" wire:click="showRecoveryCodes" />
                @endif
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.disable_two_factor')) }}"
                               class="btn-sm btn-error" wire:click="disable" wire:confirm="{{ __('Are you sure?') }}" />
            </div>
        @elseif ($this->pending)
            <h3 class="font-semibold mb-2">{{ __('laravel-crm::lang.two_factor_finish_enabling') }}</h3>
            <p class="text-sm mb-4">{{ __('laravel-crm::lang.two_factor_finish_enabling_text') }}</p>

            @if ($this->qrCodeSvg)
                <div class="mb-4">{!! $this->qrCodeSvg !!}</div>
            @endif

            @if ($this->setupKey)
                <p class="text-sm mb-2"><strong>{{ __('laravel-crm::lang.setup_key') }}:</strong>
                    <span class="font-mono">{{ $this->setupKey }}</span>
                </p>
            @endif

            <x-mary-input wire:model="code" label="{{ ucfirst(__('laravel-crm::lang.code')) }}" inputmode="numeric" />

            <div class="mt-4 flex gap-2">
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.confirm')) }}"
                               class="btn-primary" wire:click="confirm" spinner />
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}"
                               wire:click="disable" />
            </div>
        @else
            <h3 class="font-semibold mb-2">{{ __('laravel-crm::lang.two_factor_disabled_heading') }}</h3>
            <p class="text-sm mb-4">{{ __('laravel-crm::lang.two_factor_disabled_text') }}</p>

            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.enable')) }}"
                           class="btn-primary" wire:click="enable" spinner />
        @endif
    </x-mary-card>
</div>

