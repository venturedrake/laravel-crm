<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" separator>
        <div wire:key="details" class="space-y-3">
            <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" required />
            <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" />
        </div>
    </x-mary-card>
</div>
<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.permissions')) }}" separator>
        <div wire:key="permissions" class="space-y-3">
            @foreach(\VentureDrake\LaravelCrm\Models\Permission::all() as $permission)
                <x-mary-checkbox label="{{ $permission->name }}" wire:model="permissions" value="{{ $permission->id }}" right />
                <hr />
            @endforeach
        </div>
    </x-mary-card>
</div>

