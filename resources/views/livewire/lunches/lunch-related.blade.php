<div class="grid gap-5">
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.add_lunch')) }}" separator>
        <x-mary-form wire:submit="save">
            <div class="grid gap-3" wire:key="details">
                <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.subject')) }}" />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-data="{
                    snap15(el) {
                        if (!el.value) return;
                        const d = new Date(el.value);
                        d.setMinutes(Math.round(d.getMinutes() / 15) * 15, 0, 0);
                        const p = n => String(n).padStart(2, '0');
                        el.value = `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
                        el.dispatchEvent(new Event('input'));
                    }
                }">
                    <x-mary-datetime wire:model="start_at" label="{{ ucfirst(__('laravel-crm::lang.start_at')) }}" type="datetime-local" x-on:change="snap15($event.target)" />
                    <x-mary-datetime wire:model="finish_at" label="{{ ucfirst(__('laravel-crm::lang.finish_at')) }}" type="datetime-local" x-on:change="snap15($event.target)" />
                </div>
                <x-mary-choices-offline
                        wire:model="guests"
                        label="{{ ucfirst(__('laravel-crm::lang.guests')) }}"
                        :options="$persons"
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

    @if(count($lunches) > 0)
        @foreach($lunches as $lunch)
            @livewire('crm-lunch-item', ['lunch' => $lunch, 'related' => $data[$lunch->id]['related']], key('lunch-item-'.$lunch->id))
        @endforeach
    @endif
</div>

