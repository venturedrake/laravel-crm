<div class="crm-content">
    <x-mary-header title="{{ ucwords(__('laravel-crm::lang.integrations')) }}" class="mb-5" progress-indicator></x-mary-header>
    <x-laravel-crm::integration-tabs>
        <div class="grid gap-y-5">
            <p>Connect to xero accounting to sync contacts, products, quotes & generate invoices.</p>
            <hr />
            @if(isset($tenantName))
                <div role="alert" class="alert alert-info">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 shrink-0 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>You are connected to the Xero organization <strong>{{ $tenantName }}</strong>.</span>
                </div>
                <hr />
                <x-mary-button label="Disconnect Xero" link="{{ route('laravel-crm.integrations.xero.disconnect')  }}" class="btn btn-success btn-outline" />
                <hr />
                <h3>Settings</h3>
                <form wire:submit.prevent="updateSettings">
                    <x-mary-toggle label="Sync Contacts" wire:model="setting_contacts" right />
                    <x-mary-toggle label="Sync Products" wire:model="setting_products" right />
                   {{-- <x-mary-toggle label="Create & Update Quotes" wire:model="setting_quotes" right />--}}
                    <x-mary-toggle label="Create & Update Invoices" wire:model="setting_invoices" right />
                    <hr />
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">{{ ucwords(__('laravel-crm::lang.save_changes')) }}</button>
                    </div>
                </form>
            @elseif(! $installed)
                <div role="alert" class="alert alert-warning">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 shrink-0 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19H19a2 2 0 001.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16A2 2 0 005.07 19z"></path>
                    </svg>
                    <span>The Xero integration needs the <code>dcblogdev/laravel-xero</code> package, which is not installed. Run <code>composer require dcblogdev/laravel-xero</code> to enable it. Note that package caps Guzzle at <code>^7</code>, so it will hold your app back from Guzzle 8.</span>
                </div>
            @else
                <x-mary-button label="Connect to Xero" link="{{ route('laravel-crm.integrations.xero.connect') }}" class="btn btn-outline" />
            @endif
        </div>
    </x-laravel-crm::integration-tabs>
</div>
