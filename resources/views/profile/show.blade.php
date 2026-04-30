<x-crm::app-layout>
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.profile')) }}" subtitle="{{ __('laravel-crm::lang.profile_subtitle') }}" class="mb-5" />

    <div class="grid gap-8">
        <livewire:crm-profile-update-information />

        <livewire:crm-profile-update-password />

        @if(class_exists(\PragmaRX\Google2FA\Google2FA::class))
            <div class="border-t border-base-content/10"></div>
            <livewire:crm-profile-two-factor />
        @endif

        <livewire:crm-profile-browser-sessions />

        <livewire:crm-profile-delete-user />
    </div>
</x-crm::app-layout>

