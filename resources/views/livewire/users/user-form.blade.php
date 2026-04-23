<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" class="mb-5" separator>
    <div class="grid gap-3" wire:key="user-details">
            <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" />
            <x-mary-input wire:model="email" label="{{ ucfirst(__('laravel-crm::lang.email')) }}" />
            <x-mary-toggle wire:model="crm_access" label="{{ ucfirst(__('laravel-crm::lang.CRM_access')) }}" />
            <x-mary-select wire:model="role" :options="$roles" label="{{ ucfirst(__('laravel-crm::lang.CRM_role')) }}" />
        </div>
    </x-mary-card>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.teams')) }}" separator>
    <div class="grid gap-3" wire:key="user-teams">
            @foreach($teams as $team)
                <x-mary-checkbox label="{{ $team->name }}" value="{{ $team->id }}" wire:model="userTeams" right />
                <hr />
            @endforeach
        </div>
    </x-mary-card>>
</div>
<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.phone_numbers')) }}" class="mb-5" separator>
        <div class="grid gap-3" wire:key="user-contacts">
             CONTACTS PLACEHOLDER
        </div>
    </x-mary-card>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.addresses')) }}" separator>
        <div class="grid gap-3" wire:key="user-contacts">
            ADDRESS PLACEHOLDER
        </div>
    </x-mary-card>
</div>