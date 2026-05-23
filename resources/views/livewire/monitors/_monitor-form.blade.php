<div class="grid lg:grid-cols-2 gap-5">
    <x-mary-input wire:model="url" label="{{ ucfirst(__('laravel-crm::lang.website_url')) }}" placeholder="https://example.com" class="lg:col-span-2" />
    <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.friendly_name')) }}" />
    <x-mary-select wire:model="type" :options="$this->typeOptions()" label="{{ ucfirst(__('laravel-crm::lang.type')) }}" />
    <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="3" class="lg:col-span-2" />
    <x-mary-select wire:model="method" :options="$this->methodOptions()" label="{{ ucfirst(__('laravel-crm::lang.method')) }}" />
    <x-mary-input wire:model="expected_status_code" type="number" label="{{ ucfirst(__('laravel-crm::lang.expected_status_code')) }}" />
    <x-mary-input wire:model="interval" type="number" min="1" label="{{ ucfirst(__('laravel-crm::lang.interval')) }} (min)" />
    <x-mary-input wire:model="timeout" type="number" label="{{ ucfirst(__('laravel-crm::lang.timeout')) }} (s)" />
    <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.owner')) }}" wire:model="user_owner_id" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\usersOptions(false)" />
    <x-mary-toggle wire:model="is_active" label="{{ ucfirst(__('laravel-crm::lang.active')) }}" />
</div>
