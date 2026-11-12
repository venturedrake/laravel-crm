{{-- The fields of a logged email, shared by the log form and the edit form. --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <x-mary-select
        wire:model.live="direction"
        label="{{ ucfirst(__('laravel-crm::lang.direction')) }}"
        :options="[
            ['id' => 'outbound', 'name' => ucfirst(__('laravel-crm::lang.sent'))],
            ['id' => 'inbound', 'name' => ucfirst(__('laravel-crm::lang.received'))],
        ]" />
    <x-mary-datetime wire:model="sent_at" label="{{ ucfirst(__('laravel-crm::lang.date')) }}" type="datetime-local" />
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <x-mary-input wire:model="from_address" label="{{ ucfirst(__('laravel-crm::lang.from')) }}" icon="far.envelope" />
    <x-mary-input wire:model="from_name" label="{{ ucfirst(__('laravel-crm::lang.from_name')) }}" />
</div>
<x-mary-input wire:model="to" label="{{ ucfirst(__('laravel-crm::lang.to')) }}" hint="{{ ucfirst(__('laravel-crm::lang.email_addresses_hint')) }}" />
<x-mary-input wire:model="cc" label="{{ __('laravel-crm::lang.cc') }}" />
<x-mary-input wire:model="subject" label="{{ ucfirst(__('laravel-crm::lang.subject')) }}" />

{{-- A contenteditable box, so an email pasted from a mail client keeps its
     formatting. What is typed or pasted is cleaned on the server before it
     is stored. --}}
@include('laravel-crm::livewire.email-messages.partials.body-style')
<div>
    <div class="text-xs font-semibold mb-1">{{ ucfirst(__('laravel-crm::lang.body')) }}</div>
    <div
        wire:ignore
        x-data="{
            // A rule at the very start of the box cannot be selected to delete
            // it, and a pasted reply starts with one: take it out.
            dropLeadingRules() {
                let node = $refs.editor;
                while (node) {
                    const first = [...node.childNodes].find(child => child.nodeType !== 3 || child.textContent.trim() !== '');
                    if (! first || first.nodeType !== 1) { return }
                    if (first.tagName === 'HR') { first.remove(); node = $refs.editor; continue }
                    node = first;
                }
            },
        }"
        x-init="
            $refs.editor.innerHTML = $wire.body_html ?? '';
            $wire.$watch('body_html', value => {
                if ((value ?? '') !== $refs.editor.innerHTML) { $refs.editor.innerHTML = value ?? '' }
            });
        "
    >
        <div
            x-ref="editor"
            contenteditable="true"
            role="textbox"
            aria-multiline="true"
            aria-label="{{ ucfirst(__('laravel-crm::lang.body')) }}"
            class="textarea w-full crm-email-body crm-email-editor"
            @input="dropLeadingRules(); $wire.body_html = $refs.editor.innerHTML"
        ></div>
    </div>
    @error('body_html')
        <div class="text-error text-xs mt-1">{{ $message }}</div>
    @enderror
</div>

<x-mary-choices
    wire:model="guests"
    label="{{ ucfirst(__('laravel-crm::lang.other_people_involved')) }}"
    :options="$guestOptions"
    search-function="searchGuests"
    :min-chars="1"
    placeholder="Search ..."
    searchable />
<x-mary-input wire:model="attachment_names" label="{{ ucfirst(__('laravel-crm::lang.attachment_names')) }}" hint="{{ ucfirst(__('laravel-crm::lang.attachment_names_hint')) }}" />
