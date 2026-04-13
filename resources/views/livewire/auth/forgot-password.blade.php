<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.forgot_password')) }}" shadow class="bg-base-100">

        <p class="text-sm text-base-content/70 mb-4">
            {{ ucfirst(__('laravel-crm::lang.forgot_password_instructions')) }}
        </p>

        <form wire:submit="sendResetLink">
            <x-mary-input
                label="{{ ucfirst(__('laravel-crm::lang.email_address')) }}"
                wire:model="email"
                type="email"
                icon="o-envelope"
                required
                autofocus
            />

            <x-mary-button
                label="{{ ucfirst(__('laravel-crm::lang.send_password_reset_link')) }}"
                type="submit"
                class="btn-primary w-full mt-6"
                spinner="sendResetLink"
            />
        </form>

        <x-slot:actions>
            <a href="{{ route('laravel-crm.login') }}" class="text-sm link link-primary">
                {{ ucfirst(__('laravel-crm::lang.back_to_login')) }}
            </a>
        </x-slot:actions>
    </x-mary-card>
</div>

