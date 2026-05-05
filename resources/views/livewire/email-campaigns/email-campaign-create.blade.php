<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.create')) }} {{ __('laravel-crm::lang.email_campaign') }}" />

    <x-mary-form wire:submit="save">
        <x-mary-card shadow>
            @include('laravel-crm::livewire.email-campaigns.email-campaign-form')
        </x-mary-card>

        <x-mary-card shadow class="mt-5" title="{{ ucfirst(__('laravel-crm::lang.send')) }}">
            <div class="grid lg:grid-cols-2 gap-5">
                <x-mary-radio
                    wire:model.live="send_mode"
                    :options="[
                        ['id' => 'now', 'name' => ucfirst(__('laravel-crm::lang.send_now'))],
                        ['id' => 'schedule', 'name' => ucfirst(__('laravel-crm::lang.schedule_send'))],
                    ]"
                    label="{{ ucfirst(__('laravel-crm::lang.send')) }}"
                />
                @if($send_mode === 'schedule')
                    <x-mary-input wire:model="scheduled_at" type="datetime-local" label="{{ ucfirst(__('laravel-crm::lang.scheduled_at')) }}" />
                @endif
            </div>
        </x-mary-card>

        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" link="{{ route('laravel-crm.email-campaigns.index') }}" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
