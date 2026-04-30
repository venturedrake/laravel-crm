<div>
    <x-mary-form wire:submit="updatePassword">
        <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.update_password')) }}"
                     subtitle="{{ __('laravel-crm::lang.update_password_subtitle') }}" separator>
            <div class="grid gap-4">
                <x-mary-input wire:model="current_password" type="password" autocomplete="current-password"
                              label="{{ ucfirst(__('laravel-crm::lang.current_password')) }}" required />
                <x-mary-input wire:model="password" type="password" autocomplete="new-password"
                              label="{{ ucfirst(__('laravel-crm::lang.new_password')) }}" required />
                <x-mary-input wire:model="password_confirmation" type="password" autocomplete="new-password"
                              label="{{ ucfirst(__('laravel-crm::lang.confirm_password')) }}" required />
            </div>

            <x-slot:actions>
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" type="submit"
                               class="btn-primary" spinner="updatePassword" />
            </x-slot:actions>
        </x-mary-card>
    </x-mary-form>
</div>

