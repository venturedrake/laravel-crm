<div class="text-left">
    <x-mary-button @click="$wire.showSendPurchaseOrder = true" class="btn-sm btn-outline"  label="{{ ucfirst(__('laravel-crm::lang.send')) }}" />

    <x-mary-drawer
            wire:model="showSendPurchaseOrder"
            title="{{ ucfirst(__('laravel-crm::lang.send_purchase_order')) }}"
            separator
            with-close-button
            close-on-escape
            class="w-11/12 lg:w-1/3"
            right
    >
        <x-mary-form wire:submit="send">
            <div class="space-y-3"> 
                <x-mary-input wire:model="to" label="{{ ucfirst(__('laravel-crm::lang.to')) }}" />
                <x-mary-input wire:model="subject" label="{{ ucfirst(__('laravel-crm::lang.subject')) }}" />
                <x-mary-textarea wire:model="message" label="{{ ucfirst(__('laravel-crm::lang.message')) }}" rows="10" />
                <x-mary-checkbox wire:model="cc" label="{{ ucfirst(__('laravel-crm::lang.send_me_a_copy')) }}" />
            </div>
    
            <x-slot:actions>
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" @click="$wire.showSendPurchaseOrder = false" />
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.send')) }}" class="btn-primary" type="submit" spinner="send" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-drawer>
</div>
