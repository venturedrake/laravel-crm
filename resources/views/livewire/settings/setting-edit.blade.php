<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.settings')) }}" class="mb-5" progress-indicator ></x-mary-header>
    <x-mary-form wire:submit="save">
        <div class="grid lg:grid-cols-2 gap-5">
            <div>
                <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.general')) }}" separator>
                    <div class="grid gap-3" wire:key="details">
                        <x-mary-input wire:model="organizationName" label="{{ ucfirst(__('laravel-crm::lang.organization_name')) }}" required />
                        <x-mary-input wire:model="vatNumber" label="{{ ucfirst(__('laravel-crm::lang.vat_number')) }}" />
                        @if ($logoFile)
                            <div>
                                <img src="{{ $logoFile->temporaryUrl() }}">
                            </div>
                        @elseif($logo)
                            <div>
                                <img src=" {{ ($logo) ? asset('storage/'.$logo) : 'https://via.placeholder.com/140x90' }}" class="img-fluid" width="200" />
                            </div>
                        @endif
                        <x-mary-file wire:model="logoFile" label="{{ ucfirst(__('laravel-crm::lang.logo')) }}" />
                        <x-mary-select wire:model="country" label="{{ ucfirst(__('laravel-crm::lang.country')) }}" :options="$countries" required />
                        <x-mary-select wire:model="language" label="{{ ucfirst(__('laravel-crm::lang.language')) }}" :options="$languages" required />
                        <x-mary-select wire:model="currency" label="{{ ucfirst(__('laravel-crm::lang.currency')) }}" :options="$currencies" required />
                        <x-mary-select wire:model="timezone" label="{{ ucfirst(__('laravel-crm::lang.timezone')) }}" :options="$timezones" required />
                        <x-mary-select wire:model="dateFormat" label="{{ ucfirst(__('laravel-crm::lang.date_format')) }}" :options="$dateFormats" required />
                        <x-mary-select wire:model="timeFormat" label="{{ ucfirst(__('laravel-crm::lang.time_format')) }}" :options="$timeFormats" required />
                        <x-mary-input wire:model="taxName" label="{{ ucfirst(__('laravel-crm::lang.default_tax_name')) }}" />
                        <x-mary-input wire:model="taxRate" label="{{ ucfirst(__('laravel-crm::lang.default_tax_rate')) }}" suffix="%" />
                        @hasleadsenabled
                             <x-mary-input wire:model="leadPrefix" label="{{ ucfirst(__('laravel-crm::lang.lead_prefix')) }}" />
                        @endhasleadsenabled
                        @hasdealsenabled
                            <x-mary-input wire:model="dealPrefix" label="{{ ucfirst(__('laravel-crm::lang.deal_prefix')) }}" />
                        @endhasdealsenabled
                        @hasquotesenabled
                            <x-mary-input wire:model="quotePrefix" label="{{ ucfirst(__('laravel-crm::lang.quote_prefix')) }}" />
                        @endhasquotesenabled
                        @hasordersenabled
                            <x-mary-input wire:model="orderPrefix" label="{{ ucfirst(__('laravel-crm::lang.order_prefix')) }}" />
                        @endhasordersenabled
                        @hasinvoicesenabled
                            <x-mary-input wire:model="invoicePrefix" label="{{ ucfirst(__('laravel-crm::lang.invoice_prefix')) }}" />
                        @endhasinvoicesenabled
                        @hasdeliveriesenabled
                            <x-mary-input wire:model="deliveryPrefix" label="{{ ucfirst(__('laravel-crm::lang.delivery_prefix')) }}" />
                        @endhasdeliveriesenabled
                        @haspurchaseordersenabled
                            <x-mary-input wire:model="purchaseOrderPrefix" label="{{ ucfirst(__('laravel-crm::lang.purchase_order_prefix')) }}" />
                        @endhaspurchaseordersenabled
                        @hasquotesenabled
                            <x-mary-textarea wire:model="quoteTerms" label="{{ ucfirst(__('laravel-crm::lang.quote_terms')) }}" rows="5" />
                        @endhasquotesenabled
                        @hasinvoicesenabled
                            <x-mary-textarea wire:model="invoiceContactDetails" label="{{ ucfirst(__('laravel-crm::lang.invoice_contact_details')) }}" rows="5" />
                            <x-mary-textarea wire:model="invoiceTerms" label="{{ ucfirst(__('laravel-crm::lang.invoice_terms')) }}" rows="5" />
                            <x-mary-textarea wire:model="invoicePaymentInstructions" label="{{ ucfirst(__('laravel-crm::lang.invoice_payment_instructions')) }}" rows="5" />
                        @endhasinvoicesenabled
                        @haspurchaseordersenabled
                            <x-mary-textarea wire:model="purchaseOrderTerms" label="{{ ucfirst(__('laravel-crm::lang.purchase_order_terms')) }}" rows="5" />
                            <x-mary-textarea wire:model="purchaseOrderDeliveryInstructions" label="{{ ucfirst(__('laravel-crm::lang.purchase_order_delivery_instructions')) }}" rows="5" />
                        @endhaspurchaseordersenabled
                        <div class="mt-3">
                            <x-mary-toggle wire:model="dynamicProducts" class="self-start">
                            <x-slot:label>
                                {{ ucfirst(__('laravel-crm::lang.allow_creating_products_when_creating_quotes_orders_and_invoices')) }}
                            </x-slot:label>
                        </x-mary-toggle>
                        </div>
                        <div class="mt-1">
                        <x-mary-toggle wire:model="showRelatedActivity" class="self-start">
                            <x-slot:label>
                                {{ ucfirst(__('laravel-crm::lang.show_related_contact_activity')) }}
                            </x-slot:label>
                            </x-mary-toggle>
                        </div>
                    </div>
                </x-mary-card>
            </div>
            <div>
                <livewire:crm-model-phones />
                <livewire:crm-model-emails />
                <livewire:crm-model-addresses />
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save_changes')) }}" class="btn-primary text-white" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
