@php
    // 'purchase-order' is the wire slug; 'purchase_order' is the lang key.
    $recordLabel = $type
        ? __('laravel-crm::lang.'.str_replace('-', '_', $type))
        : __('laravel-crm::lang.record');
@endphp

{{-- x-init sets the flag every <x-crm-get-link-button /> checks before
     dispatching. A host that published layouts/app.blade.php before this
     shipped never renders this component, the flag stays undefined, and the
     buttons fall back to opening the portal page in a new tab. --}}
<div x-data x-init="window.crmGetLinkMounted = true">
    <x-mary-modal wire:model="show" title="{{ ucfirst(__('laravel-crm::lang.get_link')) }}" :subtitle="$title">
        <p class="mb-4 text-sm">
            {{ __('laravel-crm::lang.get_link_hint', ['record' => $recordLabel]) }}
            {{ __('laravel-crm::lang.link_expires_days', ['days' => \VentureDrake\LaravelCrm\Support\PortalLink::DAYS]) }}
        </p>

        {{-- copied is Alpine-local rather than a Livewire property: the
             clipboard write happens entirely in the browser, and a round trip
             just to flip a label back after 1.5s would be absurd. --}}
        <div x-data="{ copied: false }" class="flex items-end gap-2">
            <div class="grow">
                <x-mary-input
                    readonly
                    wire:model="url"
                    class="font-mono text-xs"
                    x-on:focus="$event.target.select()" />
            </div>

            {{-- Two buttons swapped by x-show rather than one with a bound
                 label: the confirmation has to survive being read at a glance,
                 and a Blade expression inside an x-bind attribute is a
                 quoting trap for no gain. --}}
            <x-mary-button
                x-show="copied"
                x-cloak
                icon="o-check"
                title="{{ ucfirst(__('laravel-crm::lang.copied')) }}"
                class="btn-square btn-success text-white" />

            <x-mary-button
                x-show="! copied"
                icon="o-clipboard-document"
                title="{{ ucfirst(__('laravel-crm::lang.copy')) }}"
                class="btn-square btn-outline"
                x-on:click="
                    const done = () => { copied = true; setTimeout(() => copied = false, 1500); };
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText($wire.url).then(done).catch(() => {
                            const ta = document.createElement('textarea');
                            ta.value = $wire.url; ta.style.position = 'fixed'; ta.style.opacity = 0;
                            document.body.appendChild(ta); ta.select();
                            try { document.execCommand('copy'); done(); } catch (e) {}
                            document.body.removeChild(ta);
                        });
                    } else {
                        const ta = document.createElement('textarea');
                        ta.value = $wire.url; ta.style.position = 'fixed'; ta.style.opacity = 0;
                        document.body.appendChild(ta); ta.select();
                        try { document.execCommand('copy'); done(); } catch (e) {}
                        document.body.removeChild(ta);
                    }
                " />
        </div>

        @if($canMarkSent)
            <div class="mt-4">
                <x-mary-checkbox wire:model="markAsSent" label="{{ ucfirst(__('laravel-crm::lang.mark_as_sent')) }}" />
            </div>
        @endif

        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" @click="$wire.show = false" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.ok')) }}" class="btn-primary" wire:click="confirm" spinner />
        </x-slot:actions>
    </x-mary-modal>
</div>
