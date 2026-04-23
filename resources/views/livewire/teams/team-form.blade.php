<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" separator>
        <div class="grid gap-3" wire:key="team-details">
            <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" />
        </div>
    </x-mary-card>
</div>
<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.users')) }}" separator>
        <div class="grid gap-3" wire:key="team-users">
            @foreach($users as $user)
                <x-mary-checkbox label="{{ $user->name }}" value="{{ $user->id }}" wire:model="teamUsers" right />
                <hr />
            @endforeach
        </div>
    </x-mary-card>
</div>

