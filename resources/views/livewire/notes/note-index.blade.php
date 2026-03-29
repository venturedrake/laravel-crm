<div class="grid gap-5">
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
    @if(count($notes) > 0)
        @foreach($notes as $note)
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.notes')) }}">
                <x-slot:title>
                    {{ $note->created_at->diffForHumans() }} - {{ $note->createdByUser->name }}
                </x-slot:title>
                <div>
                    {!! $note->content !!}
                    @if($note->noted_at)
                        <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.noted_at')) }} {{ $note->noted_at->format('h:i A') }} on {{ $note->noted_at->toFormattedDateString() }}" class="badge badge-neutral text-white"  />
                    @endif
                </div>
            </x-mary-card>
        @endforeach
    @endif    
</div>