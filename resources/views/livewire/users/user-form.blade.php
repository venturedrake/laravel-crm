<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" class="mb-5" separator>
    <div class="grid gap-3" wire:key="user-details">
            <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" />
            <x-mary-input wire:model="email" label="{{ ucfirst(__('laravel-crm::lang.email')) }}" />
            @if($isCreate ?? false)
                <x-mary-input wire:model="password" type="password" label="{{ ucfirst(__('laravel-crm::lang.password')) }}" />
                <x-mary-input wire:model="password_confirmation" type="password" label="{{ ucfirst(__('laravel-crm::lang.confirm_password')) }}" />
            @endif
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
    <x-crm-phones :$phones :$phoneTypes />
    <x-crm-addresses :$addresses :$addressTypes :$countries />
</div>