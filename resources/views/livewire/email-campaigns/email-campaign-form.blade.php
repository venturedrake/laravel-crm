<div class="grid lg:grid-cols-2 gap-5">
    <div>
        <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" />
    </div>
    <div>
        <x-mary-select wire:model.live="email_template_id"
                       :options="$templates"
                       option-label="name"
                       option-value="id"
                       label="{{ ucfirst(__('laravel-crm::lang.email_template')) }}"
                       placeholder="-" />
    </div>
    <div class="lg:col-span-2">
        <x-mary-input wire:model="subject" label="{{ ucfirst(__('laravel-crm::lang.subject')) }}" />
    </div>
    <div class="lg:col-span-2">
        <label class="fieldset-legend">{{ ucfirst(__('laravel-crm::lang.body')) }}</label>
        <textarea wire:model="body" rows="14" class="textarea textarea-bordered w-full font-mono text-sm"></textarea>
    </div>
</div>
