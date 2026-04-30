<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.delete_account')) }}"
                 subtitle="{{ __('laravel-crm::lang.delete_account_subtitle') }}">
        <div class="border-t border-base-content/10"></div>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.delete_account')) }}"
                           class="btn-error text-white" wire:click="confirmUserDeletion" />
        </x-slot:actions>
    </x-mary-card>

    <x-mary-modal wire:model="confirmingUserDeletion" title="{{ ucfirst(__('laravel-crm::lang.delete_account')) }}">
        <p class="mb-4">{{ __('laravel-crm::lang.delete_account_confirm') }}</p>
        <x-mary-input wire:model="password" type="password" autocomplete="current-password"
                      label="{{ ucfirst(__('laravel-crm::lang.password')) }}"
                      placeholder="{{ ucfirst(__('laravel-crm::lang.password')) }}" />

        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" @click="$wire.confirmingUserDeletion = false" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.delete_account')) }}"
                           class="btn-error" wire:click="deleteUser" spinner />
        </x-slot:actions>
    </x-mary-modal>
</div>

