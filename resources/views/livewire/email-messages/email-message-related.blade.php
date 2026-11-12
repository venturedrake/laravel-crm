<div class="grid gap-5">
    @can('create crm emails')
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.log_email')) }}" separator>
        <x-mary-form wire:submit="save">
            <div class="grid gap-3" wire:key="details">
                @include('laravel-crm::livewire.email-messages.partials.fields')
            </div>
            <x-slot:actions>
                <x-mary-button
                    wire:click="fillFromPaste"
                    label="{{ ucfirst(__('laravel-crm::lang.fill_from_pasted_email')) }}"
                    tooltip="{{ ucfirst(__('laravel-crm::lang.fill_from_pasted_email_hint')) }}"
                    icon="far.paste"
                    type="button"
                    spinner="fillFromPaste" />
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-card>
    @endcan

    @can('view crm emails')
        @foreach($emailMessages as $emailMessage)
            @livewire('crm-emailmessage-item', ['emailmessage' => $emailMessage, 'related' => $data[$emailMessage->id]['related']], key('email-message-item-'.$emailMessage->id))
        @endforeach
    @endcan
</div>
