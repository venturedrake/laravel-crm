<x-mary-modal wire:model="confirmingSend" title="{{ ucfirst(__('laravel-crm::lang.send_now')) }}?">
    <p class="mb-4">{{ trans_choice('laravel-crm::lang.confirm_send_now', $recipientCount ?? 0, ['count' => number_format($recipientCount ?? 0)]) }}</p>

    <x-slot:actions>
        <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back')) }}" @click="$wire.confirmingSend = false" />
        <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.send_now')) }}" class="btn-primary text-white" wire:click="save(true)" spinner="save" />
    </x-slot:actions>
</x-mary-modal>
