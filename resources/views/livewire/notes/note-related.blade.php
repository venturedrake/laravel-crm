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
            <x-mary-card>
                <div class="grid gap-3">
                    <div class="flex justify-between items-start">
                        <div class="font-bold text-lg">
                            {{ $note->created_at->diffForHumans() }} - {{ $note->createdByUser->name }}<br />
                            @if($data[$note->id]['related'])
                                <div class="flex flex-row gap-2 mt-1">
                                    @if(class_basename($note->noteable->getMorphClass()) == 'Person')
                                        <x-mary-icon name="fas.user-circle" class="text-sm" />
                                            <span class="text-sm">
                                            <a href="{{ route('laravel-crm.people.show',$note->noteable) }}" class="link link-hover link-primary">{{ $note->noteable->name }}</a>
                                        </span>
                                    @elseif(class_basename($note->noteable->getMorphClass()) == 'Organization')
                                        <x-mary-icon name="fas.building" class="text-sm" />
                                        <span class="text-sm">
                                            <a href="{{ route('laravel-crm.organizations.show',$note->noteable) }}" class="link link-hover link-primary">{{ $note->noteable->name }}</a>
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <x-mary-dropdown right>
                            <x-slot:trigger>
                                <x-mary-icon name="o-ellipsis-horizontal" /> 
                            </x-slot:trigger>
                            <x-mary-menu-item wire:click="edit({{ $note->id }})" title="{{ ucfirst(__('laravel-crm::lang.edit')) }}" />
                            @if($note->pinned == 1)
                                <x-mary-menu-item wire:click="unpin({{ $note->id }})" title="{{ ucfirst(__('laravel-crm::lang.unpin_this_note')) }}" />
                            @else
                                <x-mary-menu-item wire:click="pin({{ $note->id }})" title="{{ ucfirst(__('laravel-crm::lang.pin_this_note')) }}" />
                            @endif
                            <x-mary-menu-item onclick="modalDeleteNote{{ $note->id }}.showModal()" title="{{ ucfirst(__('laravel-crm::lang.delete')) }}" />
                        </x-mary-dropdown>
                    </div>
                    
                    @if(! empty($data[$note->id]['editing']))
                        <x-mary-form wire:submit="update({{ $note->id }})">
                            <div class="grid gap-3" wire:key="note-update-{{ $note->id }}">
                                <x-mary-textarea wire:model="data.{{ $note->id }}.content" label="{{ ucfirst(__('laravel-crm::lang.note')) }}" rows="5" />
                                <x-mary-datetime wire:model="data.{{ $note->id }}.noted_at" label="{{ ucfirst(__('laravel-crm::lang.noted_at')) }}" type="datetime-local" />
                            </div>
                            <x-slot:actions>
                                <x-mary-button wire:click="cancel({{ $note->id }})" label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" type="button" spinner="save" />
                                <x-mary-button wire:click="update({{ $note->id }})" label="{{ ucfirst(__('laravel-crm::lang.save_changes')) }}" class="btn-primary text-white" type="button" spinner="save" />
                            </x-slot:actions>
                        </x-mary-form>
                    @else
                        <div>
                            {!! $note->content !!}
                        </div>
                        
                        @if($note->noted_at)
                            <div>
                                <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.noted_at')) }} {{ $note->noted_at->format('h:i A') }} on {{ $note->noted_at->toFormattedDateString() }}" class="badge-soft badge-sm" />
                            </div>
                        @endif
                        <x-crm-delete-confirm model="note" id="{{ $note->id }}" />
                    @endif
                </div>
            </x-mary-card>
        @endforeach
    @endif
</div>