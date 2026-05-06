<div class="grid lg:grid-cols-10 gap-5">
    <div class="lg:col-span-2">
        @include('laravel-crm::layouts.partials.nav-settings')
    </div>
    <div class="lg:col-span-8">
        <div class="crm-content">
            <x-mary-header title="ClickSend" class="mb-5" progress-indicator></x-mary-header>
            <x-mary-card shadow separator>
                <div class="grid gap-y-5">
                    <p>{{ __('laravel-crm::lang.clicksend_connect_intro') }}</p>

                    @if(! $verified)
                        <div role="alert" class="alert alert-info alert-soft">
                            <span>
                                {{ __('laravel-crm::lang.sign_up_for_clicksend') }}
                                <a href="https://clicksend.com/?u=47224" target="_blank" class="link">clicksend.com</a>
                            </span>
                        </div>
                    @else
                        <div role="alert" class="alert alert-success alert-soft">
                            <span>
                                {{ __('laravel-crm::lang.clicksend_connected') }}
                                @if($balance !== null)
                                    — {{ ucfirst(__('laravel-crm::lang.balance')) }}: ${{ number_format($balance, 2) }}
                                @endif
                            </span>
                        </div>
                    @endif

                    @if($errorMessage && ! $verified)
                        <div role="alert" class="alert alert-warning alert-soft">
                            <span>{{ $errorMessage }}</span>
                        </div>
                    @endif

                    <hr />

                    <form wire:submit="save" class="grid gap-5">
                        <x-mary-input
                            wire:model="username_input"
                            label="{{ ucfirst(__('laravel-crm::lang.clicksend_username')) }}"
                            placeholder="{{ $username_mask ?? '' }}"
                        />
                        <x-mary-input
                            wire:model="api_key_input"
                            type="password"
                            label="{{ ucfirst(__('laravel-crm::lang.clicksend_api_key')) }}"
                            placeholder="{{ $api_key_mask ?? '' }}"
                        />
                        <x-mary-input wire:model="default_from" label="{{ ucfirst(__('laravel-crm::lang.clicksend_default_from')) }}" hint="{{ __('laravel-crm::lang.sender_id_hint') }}" />

                        <div class="flex gap-2">
                            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save_changes')) }}" class="btn-primary text-white" type="submit" spinner="save" />
                            @if($verified)
                                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.disconnect')) }}" wire:click="disconnect" wire:confirm="Disconnect ClickSend?" class="btn-outline btn-error" />
                            @endif
                        </div>
                    </form>
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
