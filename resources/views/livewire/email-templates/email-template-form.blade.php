<div class="grid gap-5">
    <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" />
    <x-mary-input wire:model="subject" label="{{ ucfirst(__('laravel-crm::lang.subject')) }}" />
    <div>
        <label class="fieldset-legend">{{ ucfirst(__('laravel-crm::lang.body')) }}</label>
        <textarea wire:model="body" rows="16" class="textarea textarea-bordered w-full font-mono text-sm"></textarea>
    </div>
</div>
