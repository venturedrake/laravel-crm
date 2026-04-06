<div class="grid gap-5">
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.add_task')) }}" separator>
        <x-mary-form wire:submit="save">
            <div class="grid gap-3" wire:key="details">
                <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.task')) }}" />
                <x-mary-datetime wire:model="due_at" label="{{ ucfirst(__('laravel-crm::lang.whens_it_due')) }}" type="datetime-local"  />
                <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.further_details')) }}" rows="5" />
                <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.who_requested_the_task')) }}" wire:model="user_owner_id" :options="$users" />
                <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.who_is_responsible')) }}" wire:model="user_assigned_id" :options="$users" />
            </div>
            <x-slot:actions>
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-card>

    @if(count($tasks) > 0)
        @foreach($tasks as $task)
            <x-mary-card>
                <div class="grid gap-3">
                    <div class="flex justify-between items-start">
                        <div class="font-bold text-lg">
                            {{ $task->name }}
                            <div class="flex flex-row gap-1 mt-1">
                                @if($task->completed_at)
                                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.complete')) }}" class="badge-sm badge-success" />
                                @else
                                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.pending')) }}" class="badge-sm badge-primary" />
                                @endif
                                @if($task->due_at)
                                   <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.due')) }} {{ $task->due_at->format('h:i A') }} on {{ $task->due_at->toFormattedDateString() }}" class="badge-soft badge-sm" />
                                @endif
                            </div>
                            @if($data[$task->id]['related'])
                                <div class="flex flex-row gap-2 mt-1">
                                    @if(class_basename($task->taskable->getMorphClass()) == 'Person')
                                        <x-mary-icon name="fas.user-circle" class="text-sm" />
                                        <span class="text-sm">
                                            <a href="{{ route('laravel-crm.people.show', $task->taskable) }}" class="link link-hover link-primary">{{ $task->taskable->name }}</a>
                                        </span>
                                    @elseif(class_basename($task->taskable->getMorphClass()) == 'Organization')
                                        <x-mary-icon name="fas.building" class="text-sm" />
                                        <span class="text-sm">
                                            <a href="{{ route('laravel-crm.organizations.show', $task->taskable) }}" class="link link-hover link-primary">{{ $task->taskable->name }}</a>
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <x-mary-dropdown right>
                                <x-slot:trigger>
                                    <x-mary-icon name="o-ellipsis-horizontal" />
                                </x-slot:trigger>
                                <x-mary-menu-item wire:click="edit({{ $task->id }})" title="{{ ucfirst(__('laravel-crm::lang.edit')) }}" />
                                @if(! $data[$task->id]['completed_at'])
                                    <x-mary-menu-item wire:click="complete({{ $task->id }})" title="{{ ucfirst(__('laravel-crm::lang.complete')) }}" />
                                @endif
                                <x-mary-menu-item onclick="modalDeleteTask{{ $task->id }}.showModal()" title="{{ ucfirst(__('laravel-crm::lang.delete')) }}" />
                            </x-mary-dropdown>
                        </div>
                    </div>

                    @if(! empty($data[$task->id]['editing']))
                        <x-mary-form wire:submit="update({{ $task->id }})">
                            <div class="grid gap-3" wire:key="task-update-{{ $task->id }}">
                                <x-mary-input wire:model="data.{{ $task->id }}.name" label="{{ ucfirst(__('laravel-crm::lang.task')) }}" />
                                <x-mary-datetime wire:model="data.{{ $task->id }}.due_at" label="{{ ucfirst(__('laravel-crm::lang.whens_it_due')) }}" />
                                <x-mary-textarea wire:model="data.{{ $task->id }}.description" label="{{ ucfirst(__('laravel-crm::lang.further_details')) }}" rows="5" />
                                <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.who_requested_the_task')) }}" wire:model="data.{{ $task->id }}.user_owner_id" :options="$users" />
                                <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.who_is_responsible')) }}" wire:model="data.{{ $task->id }}.user_assigned_id" :options="$users" />
                            </div>
                            <x-slot:actions>
                                <x-mary-button wire:click="cancel({{ $task->id }})" label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" type="button" />
                                <x-mary-button wire:click="update({{ $task->id }})" label="{{ ucfirst(__('laravel-crm::lang.save_changes')) }}" class="btn-primary text-white" type="button" spinner="update({{ $task->id }})" />
                            </x-slot:actions>
                        </x-mary-form>
                    @else
                        @if($task->description)
                            <div>{!! $task->description !!}</div>
                        @endif
                        <div class="flex flex-row gap-2">
                            @if($task->ownerUser)
                                <small>{{ ucfirst(__('laravel-crm::lang.requested_by')) }} <a href="{{ route('laravel-crm.users.show', $task->assignedToUser) }}" class="link link-hover link-primary">{{ $task->assignedToUser->name }}</a></small>
                            @endif
                            @if($task->ownerUser && $task->assignedToUser)
                                <small>|</small>
                            @endif
                            @if($task->assignedToUser)
                                <small>{{ ucfirst(__('laravel-crm::lang.assigned_to')) }} <a href="{{ route('laravel-crm.users.show', $task->assignedToUser) }}" class="link link-hover link-primary">{{ $task->assignedToUser->name }}</a></small>
                            @endif
                        </div>
                        <x-crm-delete-confirm model="task" id="{{ $task->id }}" />
                    @endif
                </div>
            </x-mary-card>
        @endforeach
    @endif
</div>

