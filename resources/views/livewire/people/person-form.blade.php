<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" separator>
        <div class="grid gap-3" wire:key="person">
            <div class="grid lg:grid-cols-12 gap-5">
                <div class="lg:col-span-2">
                    <x-mary-input wire:model="title" label="{{ ucwords(__('laravel-crm::lang.title')) }}" />
                </div>
                <div class="lg:col-span-5">
                    <x-mary-input wire:model="first_name" label="{{ ucwords(__('laravel-crm::lang.first_name')) }}" />
                </div>
                <div class="lg:col-span-5">
                    <x-mary-input wire:model="last_name" label="{{ ucwords(__('laravel-crm::lang.last_name')) }}" />
                </div>
            </div>
            <div class="grid lg:grid-cols-12 gap-5">
                <div class="lg:col-span-4">
                    <x-mary-input wire:model="middle_name" label="{{ ucwords(__('laravel-crm::lang.middle_name')) }}" />
                </div>
                <div class="lg:col-span-4">
                    <x-mary-select wire:model.live="gender" label="{{ ucfirst(__('laravel-crm::lang.gender')) }}" :options="$genders" />
                </div>
                <div class="lg:col-span-4">
                    <x-mary-datepicker wire:model="birthday" label="{{ ucwords(__('laravel-crm::lang.birthday')) }}" icon="o-calendar" :config="['altFormat' => $crmDateFormat]"  />
                </div>
            </div>
            <x-mary-textarea wire:model="description" label="{{ ucwords(__('laravel-crm::lang.description')) }}" rows="5" />
            <x-mary-choices-offline
                    wire:model="labels"
                    label="{{ ucfirst(__('laravel-crm::lang.labels')) }}"
                    :options="\VentureDrake\LaravelCrm\Models\Label::get()"
                    placeholder="Search ..."
                    searchable />
            <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.owner')) }}" wire:model="user_owner_id" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\usersOptions(false)" />
        </div>
    </x-mary-card>
</div>
<div>
    <x-crm-phones :$phones :$phoneTypes />
    <x-crm-emails :$emails :$emailTypes />
    <x-crm-addresses :$addresses :$addressTypes :$countries />
</div>
