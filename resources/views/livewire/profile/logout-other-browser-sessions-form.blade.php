<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.browser_sessions')) }}"
                 subtitle="{{ __('laravel-crm::lang.browser_sessions_subtitle') }}" separator>

        @if (config('session.driver') !== 'database')
            <div class="alert alert-info">
                {{ __('laravel-crm::lang.browser_sessions_database_required') }}
            </div>
        @else
            <div class="space-y-4">
                @foreach ($this->sessions as $session)
                    <div class="flex items-center gap-3">
                        <x-mary-icon name="{{ $session->agent['is_desktop'] ? 'o-computer-desktop' : 'o-device-phone-mobile' }}" class="w-8 h-8 text-base-content/60" />
                        <div class="text-sm">
                            <div>
                                {{ $session->agent['platform'] ?: __('Unknown') }} - {{ $session->agent['browser'] ?: __('Unknown') }}
                            </div>
                            <div class="text-xs text-base-content/60">
                                {{ $session->ip_address }},
                                @if ($session->is_current_device)
                                    <span class="text-success font-semibold">{{ __('laravel-crm::lang.this_device') }}</span>
                                @else
                                    {{ __('laravel-crm::lang.last_active') }} {{ $session->last_active }}
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.logout_other_sessions')) }}"
                           class="btn-primary" wire:click="confirmLogout" />
        </x-slot:actions>
    </x-mary-card>

    <x-mary-modal wire:model="confirmingLogout" title="{{ ucfirst(__('laravel-crm::lang.logout_other_sessions')) }}">
        <p class="mb-4">{{ __('laravel-crm::lang.logout_other_sessions_confirm') }}</p>
        <x-mary-input wire:model="password" type="password" autocomplete="current-password"
                      label="{{ ucfirst(__('laravel-crm::lang.password')) }}"
                      placeholder="{{ ucfirst(__('laravel-crm::lang.password')) }}" />

        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" @click="$wire.confirmingLogout = false" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.logout_other_sessions')) }}"
                           class="btn-primary" wire:click="logoutOtherBrowserSessions" spinner />
        </x-slot:actions>
    </x-mary-modal>
</div>

