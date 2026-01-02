<div>
    <x-mary-card separator>
        <div class="grid gap-3" wire:key="product">
            <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" />
            <div class="grid lg:grid-cols-2 gap-5">
                <x-mary-input wire:model="code" label="{{ strtoupper(__('laravel-crm::lang.sku')) . ' (Stock Keeping Unit)' }}" />
                <x-mary-input wire:model="barcode" label="{{ ucwords(__('laravel-crm::lang.barcode')) . ' (ISBN, UPC, GTIN, etc)', }}" />
            </div>
            <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.category')) }}" wire:model="product_category" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\ProductCategory::all(), true)" />
            <div class="grid lg:grid-cols-2 gap-5">
                <x-mary-input wire:model="purchase_account" label="{{ ucfirst(__('laravel-crm::lang.purchase_account')) }}" />
                <x-mary-input wire:model="sales_account" label="{{ ucfirst(__('laravel-crm::lang.sales_account')) }}" />
            </div>
            <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="5" />
        </div>
    </x-mary-card>
</div>
<div>
    <x-mary-card separator>
        <div class="grid gap-3" wire:key="product">
            <div class="grid lg:grid-cols-2 gap-5">
                <x-mary-input wire:model="unit" label="{{ ucfirst(__('laravel-crm::lang.unit')) }}" />
                <x-mary-input wire:model="unit_price" label="{{ ucfirst(__('laravel-crm::lang.unit_price')) }}" prefix="$" money />
            </div>
            <div class="grid lg:grid-cols-2 gap-5">
                <x-mary-select wire:model="tax_rate_id" label="{{ ucfirst(__('laravel-crm::lang.tax_rate')) }}" :options="$taxRates" />
                <x-mary-input wire:model="tax_rate" label="{{ ucfirst(__('laravel-crm::lang.tax_rate_percent')) }}" suffix="%" readonly />
            </div>
            <div class="grid lg:grid-cols-2 gap-5">
                <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.currency')) }}" wire:model="currency" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencyOptions()" />
                <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.owner')) }}" wire:model="user_owner_id" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\usersOptions(false)" />
            </div>

        </div>
    </x-mary-card>
</div>

