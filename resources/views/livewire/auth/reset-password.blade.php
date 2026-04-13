<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.reset_password')) }}" shadow class="bg-base-100">

        <form wire:submit="resetPassword">
            <x-mary-input
                label="{{ ucfirst(__('laravel-crm::lang.email_address')) }}"
                wire:model="email"
                type="email"
                icon="o-envelope"
                required
                autofocus
            />

            <x-mary-input
                label="{{ ucfirst(__('laravel-crm::lang.password')) }}"
                wire:model="password"
                type="password"
                icon="o-lock-closed"
                required
                class="mt-4"
            />

            <x-mary-input
                label="{{ ucfirst(__('laravel-crm::lang.confirm_password')) }}"
                wire:model="password_confirmation"
                type="password"
                icon="o-lock-closed"
                required
                class="mt-4"
            />

            <x-mary-button
                label="{{ ucfirst(__('laravel-crm::lang.reset_password')) }}"
                type="submit"
                class="btn-primary w-full mt-6"
                spinner="resetPassword"
            />
        </form>

        <x-slot:actions>
            <a href="{{ route('laravel-crm.login') }}" class="text-sm link link-primary">
                {{ ucfirst(__('laravel-crm::lang.back_to_login')) }}
            </a>
        </x-slot:actions>
    </x-mary-card>
</div>

