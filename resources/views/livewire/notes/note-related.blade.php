<div class="grid gap-5 {{ ($pinned) ? 'mb-5' : null }}">
    @if(!$pinned)
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.add_note')) }}" separator>
        <x-mary-form wire:submit="save">
            <div class="grid gap-3" wire:key="details">
                <x-mary-textarea wire:model="content" label="{{ ucfirst(__('laravel-crm::lang.note')) }}" rows="5" />
                <x-mary-datetime wire:model="noted_at" label="{{ ucfirst(__('laravel-crm::lang.noted_at')) }}" type="datetime-local" />
            </div>
            <x-slot:actions>
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-card>
    @endif
    @if(count($notes) > 0)
        @foreach($notes as $note)
            @livewire('crm-note-item', ['note' => $note, 'related' => $data[$note->id]['related']], key('note-item-'.$note->id))
        @endforeach
    @endif
</div>

