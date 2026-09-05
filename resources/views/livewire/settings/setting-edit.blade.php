<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.settings')) }}" class="mb-5" progress-indicator ></x-mary-header>
    {{-- `novalidate` is load-bearing, not a preference. MaryUI spreads the
         component attributes onto the native control, so `<x-mary-input required>`
         emits a real HTML `required`. Panels for the inactive tabs are
         display:none, and a browser will not submit a form holding an invalid
         control it cannot focus — wire:submit would never fire and Save would
         look dead. Validation is enforced by rules() either way, and the red
         asterisk comes from a separate attribute check, so both survive. --}}
    <x-mary-form wire:submit="save" novalidate>
        {{-- TABS (DaisyUI radio tabs-lift + tab content — same shape as Settings → Templates).
             Each panel must stay the immediate next sibling of its own radio: the
             `:checked + .tab-content` rule is what reveals it, so a conditional
             that wraps only the input would leave its panel adjacent to the
             previous tab's radio and show two panels at once. Every @if below
             therefore wraps input and panel together. --}}
        <div role="tablist" class="tabs tabs-lift">
            @if (in_array('general', $tabs, true))
                <input type="radio"
                       name="setting-tabs"
                       role="tab"
                       class="tab"
                       aria-label="{{ ucfirst(__('laravel-crm::lang.general')) }}"
                       value="general"
                       @checked($tab === 'general')
                       wire:key="setting-tab-input-general"
                       wire:model.live="tab" />
                <div role="tabpanel" class="tab-content bg-base-100 border-base-300 p-6" wire:key="setting-tab-panel-general">
                    <div class="grid lg:grid-cols-2 gap-5 items-start">
                        <div class="grid gap-3">
                            <x-mary-input wire:model="organizationName" label="{{ ucfirst(__('laravel-crm::lang.organization_name')) }}" required />
                            <x-mary-input wire:model="vatNumber" label="{{ ucfirst(__('laravel-crm::lang.vat_number')) }}" />
                            {{-- Preview, file input and remove button are one field, so they
                                 share a single fieldset under one legend — the same
                                 `fieldset` / `fieldset-legend` pair MaryUI puts around its
                                 own inputs, which is why the label lines up with the fields
                                 above and below. `x-mary-file` is passed no label of its own
                                 here; giving it one would print a second legend mid-field.

                                 Not MaryUI's own preview slot: a non-empty slot makes the
                                 component hide the native file input and swap in a
                                 click-to-change surface, and the "Choose file" control has
                                 to stay visible.

                                 The frame reproduces the input border rather than
                                 approximating it with a utility. It cannot reuse
                                 `--input-color` directly: DaisyUI only ever declares that
                                 inside `.input` / `.select` / `.textarea` and never at the
                                 theme root, so referencing it out here resolves to nothing
                                 and takes the whole declaration down with it — a frame with
                                 no border at all. The colour below is the value `.input`
                                 itself computes; `--border` and `--radius-field` are theme
                                 tokens and are safe to read. Width, style and colour stay
                                 separate declarations so a browser without `color-mix`
                                 loses only the tint, not the border.

                                 `w-fit` keeps the frame on the image rather than stretching
                                 it across the column, and `relative` anchors the remove
                                 button to the frame's top corner. --}}
                            <fieldset class="fieldset py-0">
                                <legend class="fieldset-legend mb-0.5">{{ ucfirst(__('laravel-crm::lang.logo')) }}</legend>
                                <div wire:key="logo-preview">
                                    @if ($logoFile || $logo)
                                        <div class="relative w-fit mb-2 p-3" style="border-width: var(--border); border-style: solid; border-color: color-mix(in oklab, var(--color-base-content) 20%, transparent); border-radius: var(--radius-field);">
                                            <img src="{{ $logoFile ? $logoFile->temporaryUrl() : asset('storage/'.$logo) }}" class="block max-w-full h-auto" width="200" />
                                            <x-mary-button
                                                wire:click="deleteLogo"
                                                wire:confirm="{{ ucfirst(__('laravel-crm::lang.delete_logo_confirm')) }}"
                                                icon="o-trash"
                                                title="{{ ucfirst(__('laravel-crm::lang.delete_logo')) }}"
                                                type="button"
                                                class="btn-sm btn-square btn-error text-white shadow absolute top-2 end-2"
                                                spinner="deleteLogo" />
                                        </div>
                                    @endif
                                </div>
                                <x-mary-file wire:model="logoFile" />
                            </fieldset>
                            <x-mary-select wire:model="country" label="{{ ucfirst(__('laravel-crm::lang.country')) }}" :options="$countries" required />
                            <x-mary-select wire:model="language" label="{{ ucfirst(__('laravel-crm::lang.language')) }}" :options="$languages" required />
                            <x-mary-select wire:model="currency" label="{{ ucfirst(__('laravel-crm::lang.currency')) }}" :options="$currencies" required />
                            <x-mary-select wire:model="timezone" label="{{ ucfirst(__('laravel-crm::lang.timezone')) }}" :options="$timezones" required />
                            <x-mary-select wire:model="dateFormat" label="{{ ucfirst(__('laravel-crm::lang.date_format')) }}" :options="$dateFormats" required />
                            <x-mary-select wire:model="timeFormat" label="{{ ucfirst(__('laravel-crm::lang.time_format')) }}" :options="$timeFormats" required />
                            <x-mary-input wire:model="taxName" label="{{ ucfirst(__('laravel-crm::lang.default_tax_name')) }}" />
                            <x-mary-input wire:model="taxRate" label="{{ ucfirst(__('laravel-crm::lang.default_tax_rate')) }}" suffix="%" />
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
                        <div>
                            <x-crm-phones :$phones :$phoneTypes />
                            <x-crm-emails :$emails :$emailTypes />
                            <x-crm-addresses :$addresses :$addressTypes :$countries />
                        </div>
                    </div>
                </div>
            @endif

            @if (in_array('leads', $tabs, true))
                <input type="radio"
                       name="setting-tabs"
                       role="tab"
                       class="tab"
                       aria-label="{{ ucfirst(__('laravel-crm::lang.leads')) }}"
                       value="leads"
                       @checked($tab === 'leads')
                       wire:key="setting-tab-input-leads"
                       wire:model.live="tab" />
                <div role="tabpanel" class="tab-content bg-base-100 border-base-300 p-6" wire:key="setting-tab-panel-leads">
                    <div class="grid gap-3">
                        <x-mary-input wire:model="leadPrefix" label="{{ ucfirst(__('laravel-crm::lang.lead_prefix')) }}" />
                    </div>
                </div>
            @endif

            @if (in_array('deals', $tabs, true))
                <input type="radio"
                       name="setting-tabs"
                       role="tab"
                       class="tab"
                       aria-label="{{ ucfirst(__('laravel-crm::lang.deals')) }}"
                       value="deals"
                       @checked($tab === 'deals')
                       wire:key="setting-tab-input-deals"
                       wire:model.live="tab" />
                <div role="tabpanel" class="tab-content bg-base-100 border-base-300 p-6" wire:key="setting-tab-panel-deals">
                    <div class="grid gap-3">
                        <x-mary-input wire:model="dealPrefix" label="{{ ucfirst(__('laravel-crm::lang.deal_prefix')) }}" />
                    </div>
                </div>
            @endif

            @if (in_array('documents', $tabs, true))
                <input type="radio"
                       name="setting-tabs"
                       role="tab"
                       class="tab"
                       aria-label="{{ ucfirst(__('laravel-crm::lang.documents')) }}"
                       value="documents"
                       @checked($tab === 'documents')
                       wire:key="setting-tab-input-documents"
                       wire:model.live="tab" />
                <div role="tabpanel" class="tab-content bg-base-100 border-base-300 p-6" wire:key="setting-tab-panel-documents">
                    <div class="grid gap-3">
                        {{-- Outside the module directives on purpose: this block prints on
                             quote, order and delivery PDFs as well as invoices, so gating it
                             on invoices being enabled would leave quote/order/delivery-only
                             installs unable to fill it. --}}
                        <x-mary-textarea wire:model="pdfContactDetails" label="{{ ucfirst(__('laravel-crm::lang.pdf_contact_details')) }}" hint="{{ ucfirst(__('laravel-crm::lang.pdf_contact_details_hint')) }}" rows="5" />
                    </div>
                </div>
            @endif

            @if (in_array('quotes', $tabs, true))
                <input type="radio"
                       name="setting-tabs"
                       role="tab"
                       class="tab"
                       aria-label="{{ ucfirst(__('laravel-crm::lang.quotes')) }}"
                       value="quotes"
                       @checked($tab === 'quotes')
                       wire:key="setting-tab-input-quotes"
                       wire:model.live="tab" />
                <div role="tabpanel" class="tab-content bg-base-100 border-base-300 p-6" wire:key="setting-tab-panel-quotes">
                    <div class="grid gap-3">
                        <x-mary-input wire:model="quotePrefix" label="{{ ucfirst(__('laravel-crm::lang.quote_prefix')) }}" />
                        <x-mary-textarea wire:model="quoteTerms" label="{{ ucfirst(__('laravel-crm::lang.quote_terms')) }}" rows="5" />
                    </div>
                </div>
            @endif

            @if (in_array('orders', $tabs, true))
                <input type="radio"
                       name="setting-tabs"
                       role="tab"
                       class="tab"
                       aria-label="{{ ucfirst(__('laravel-crm::lang.orders')) }}"
                       value="orders"
                       @checked($tab === 'orders')
                       wire:key="setting-tab-input-orders"
                       wire:model.live="tab" />
                <div role="tabpanel" class="tab-content bg-base-100 border-base-300 p-6" wire:key="setting-tab-panel-orders">
                    <div class="grid gap-3">
                        <x-mary-input wire:model="orderPrefix" label="{{ ucfirst(__('laravel-crm::lang.order_prefix')) }}" />
                    </div>
                </div>
            @endif

            @if (in_array('invoices', $tabs, true))
                <input type="radio"
                       name="setting-tabs"
                       role="tab"
                       class="tab"
                       aria-label="{{ ucfirst(__('laravel-crm::lang.invoices')) }}"
                       value="invoices"
                       @checked($tab === 'invoices')
                       wire:key="setting-tab-input-invoices"
                       wire:model.live="tab" />
                <div role="tabpanel" class="tab-content bg-base-100 border-base-300 p-6" wire:key="setting-tab-panel-invoices">
                    <div class="grid gap-3">
                        <x-mary-input wire:model="invoicePrefix" label="{{ ucfirst(__('laravel-crm::lang.invoice_prefix')) }}" />
                        <x-mary-textarea wire:model="invoiceContactDetails" label="{{ ucfirst(__('laravel-crm::lang.invoice_contact_details')) }}" hint="{{ ucfirst(__('laravel-crm::lang.invoice_contact_details_hint')) }}" rows="5" />
                        <x-mary-textarea wire:model="invoiceTerms" label="{{ ucfirst(__('laravel-crm::lang.invoice_terms')) }}" rows="5" />
                        <x-mary-textarea wire:model="invoicePaymentInstructions" label="{{ ucfirst(__('laravel-crm::lang.invoice_payment_instructions')) }}" rows="5" />
                    </div>
                </div>
            @endif

            @if (in_array('deliveries', $tabs, true))
                <input type="radio"
                       name="setting-tabs"
                       role="tab"
                       class="tab"
                       aria-label="{{ ucfirst(__('laravel-crm::lang.deliveries')) }}"
                       value="deliveries"
                       @checked($tab === 'deliveries')
                       wire:key="setting-tab-input-deliveries"
                       wire:model.live="tab" />
                <div role="tabpanel" class="tab-content bg-base-100 border-base-300 p-6" wire:key="setting-tab-panel-deliveries">
                    <div class="grid gap-3">
                        <x-mary-input wire:model="deliveryPrefix" label="{{ ucfirst(__('laravel-crm::lang.delivery_prefix')) }}" />
                    </div>
                </div>
            @endif

            @if (in_array('purchase-orders', $tabs, true))
                <input type="radio"
                       name="setting-tabs"
                       role="tab"
                       class="tab"
                       aria-label="{{ ucfirst(__('laravel-crm::lang.purchase_orders')) }}"
                       value="purchase-orders"
                       @checked($tab === 'purchase-orders')
                       wire:key="setting-tab-input-purchase-orders"
                       wire:model.live="tab" />
                <div role="tabpanel" class="tab-content bg-base-100 border-base-300 p-6" wire:key="setting-tab-panel-purchase-orders">
                    <div class="grid gap-3">
                        <x-mary-input wire:model="purchaseOrderPrefix" label="{{ ucfirst(__('laravel-crm::lang.purchase_order_prefix')) }}" />
                        <x-mary-textarea wire:model="purchaseOrderTerms" label="{{ ucfirst(__('laravel-crm::lang.purchase_order_terms')) }}" rows="5" />
                        <x-mary-textarea wire:model="purchaseOrderDeliveryInstructions" label="{{ ucfirst(__('laravel-crm::lang.purchase_order_delivery_instructions')) }}" rows="5" />
                    </div>
                </div>
            @endif
        </div>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save_changes')) }}" class="btn-primary text-white" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
