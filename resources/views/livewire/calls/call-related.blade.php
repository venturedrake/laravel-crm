<div class="grid gap-5">
    @can('create crm calls')
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.add_call')) }}" separator>
        <x-mary-form wire:submit="save">
            <div class="grid gap-3" wire:key="details">
                <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.subject')) }}" />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <x-mary-datetime wire:model="start_at" label="{{ ucfirst(__('laravel-crm::lang.start_at')) }}" type="datetime-local" />
                    <x-mary-datetime wire:model="finish_at" label="{{ ucfirst(__('laravel-crm::lang.finish_at')) }}" type="datetime-local" />
                </div>
                <x-mary-choices
                        wire:model="guests"
                        label="{{ ucfirst(__('laravel-crm::lang.guests')) }}"
                        :options="$guestOptions"
                        search-function="searchGuests"
                        :min-chars="1"
                        placeholder="Search ..."
                        searchable />
                <x-mary-input wire:model="location" label="{{ ucfirst(__('laravel-crm::lang.location')) }}" />
                <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="5" />
            </div>
            <x-slot:actions>
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-card>
    @endcan

    @if(count($calls) > 0)
        @foreach($calls as $call)
            @livewire('crm-call-item', ['call' => $call, 'related' => $data[$call->id]['related']], key('call-item-'.$call->id))
        @endforeach
    @endif
</div>

