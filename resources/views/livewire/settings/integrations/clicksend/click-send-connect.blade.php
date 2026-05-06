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
                        <div role="alert" class="alert alert-info">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 shrink-0 stroke-current">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>
                                {{ __('laravel-crm::lang.sign_up_for_clicksend') }}
                                <a href="https://clicksend.com/?u=47224" target="_blank" class="link">clicksend.com</a>
                            </span>
                        </div>
                    @else
                        <div role="alert" class="alert alert-success">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>
                                {{ __('laravel-crm::lang.clicksend_connected') }}
                                @if($balance !== null)
                                    — {{ ucfirst(__('laravel-crm::lang.balance')) }}: ${{ number_format($balance, 2) }}
                                @endif
                            </span>
                        </div>
                    @endif

                    @if($errorMessage && ! $verified)
                        <div role="alert" class="alert alert-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
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
