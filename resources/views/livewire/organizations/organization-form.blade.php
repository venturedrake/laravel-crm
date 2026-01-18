<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" separator>
        <div class="grid gap-3" wire:key="organization">
            <x-mary-input wire:model="name" label="{{ ucwords(__('laravel-crm::lang.name')) }}" />
            <div class="grid lg:grid-cols-2 gap-5">
                <x-mary-select wire:model="organization_type_id" label="{{ ucfirst(__('laravel-crm::lang.type')) }}" :options="$organizationTypes" />
                <x-mary-input wire:model="vat_number" label="{{ ucfirst(__('laravel-crm::lang.vat_number')) }}" />
            </div>
            <div class="grid lg:grid-cols-2 gap-5">
                <x-mary-select wire:model="industry_id" label="{{ ucfirst(__('laravel-crm::lang.industry')) }}" :options="$industries" />
                <x-mary-select wire:model="timezone_id" label="{{ ucfirst(__('laravel-crm::lang.timezone')) }}" :options="$timezones" />
            </div>
            <div class="grid lg:grid-cols-2 gap-5">
                <x-mary-input wire:model="number_of_employees" label="{{ ucfirst(__('laravel-crm::lang.number_of_employees')) }}" />
                <x-mary-input wire:model="annual_revenue" label="{{ ucfirst(__('laravel-crm::lang.annual_revenue')) }}" prefix="$" money />
            </div>
            <x-mary-input wire:model="linkedin" label="{{ ucfirst(__('laravel-crm::lang.linkedin_company_page')) }}" prefix="https://www.linkedin.com/company/" />
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
