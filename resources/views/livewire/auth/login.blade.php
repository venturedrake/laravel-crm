<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.login')) }}" shadow class="bg-base-100">

        @if (session('status'))
            <x-mary-alert icon="o-check-circle" class="alert-success mb-4">
                {{ session('status') }}
            </x-mary-alert>
        @endif

        <form wire:submit="login" class="space-y-3">
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
               
            />

            <div class="flex items-center justify-between mt-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="remember" class="checkbox checkbox-sm checkbox-primary" />
                    <span class="text-sm">{{ ucfirst(__('laravel-crm::lang.remember_me')) }}</span>
                </label>

                <a href="{{ route('laravel-crm.password.request') }}" class="text-sm link link-primary">
                    {{ ucfirst(__('laravel-crm::lang.forgot_password')) }}?
                </a>
            </div>

            <x-mary-button
                label="{{ ucfirst(__('laravel-crm::lang.login')) }}"
                type="submit"
                class="btn-primary w-full mt-6"
                spinner="login"
            />
        </form>
    </x-mary-card>
</div>

