{{-- Collapsed to a summary until opened. `expanded` lives on the component,
     so an item stays open when the list around it is refreshed. --}}
<div class="relative mt-2">
    <x-mary-collapse wire:model.live="expanded" class="border border-base-300 bg-base-100">
        <x-slot:heading class="text-lg" style="padding-inline-end: 6rem">
            {{ $task->name }}
            <div class="flex flex-row gap-1 mt-1">
                @if($task->completed_at)
                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.complete')) }}" class="badge-sm badge-success" />
                @else
                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.pending')) }}" class="badge-sm badge-primary" />
                @endif
                @if($task->start_at)
                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.start_at')) }} {{ $task->start_at->format('h:i A') }} {{ __('laravel-crm::lang.on') }} {{ $task->start_at->toFormattedDateString() }}" class="badge-soft badge-sm" />
                @endif
                @if($task->due_at)
                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.due')) }} {{ $task->due_at->format('h:i A') }} {{ __('laravel-crm::lang.on') }} {{ $task->due_at->toFormattedDateString() }}" class="badge-soft badge-sm" />
                @endif
            </div>
            @if($related)
                <div class="flex flex-row items-center gap-2 mt-1 relative z-10">
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
        </x-slot:heading>
        <x-slot:content>
            @if($expanded)
                <div class="grid gap-3">
                    @if($editing)
                        <x-mary-form wire:submit="update">
                            <div class="grid gap-3">
                                <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.task')) }}" />
                                @include('laravel-crm::livewire.tasks.partials.schedule-fields')
                                <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.further_details')) }}" rows="5" />
                                <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.who_requested_the_task')) }}" wire:model="user_owner_id" :options="$users" />
                                <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.who_is_responsible')) }}" wire:model="user_assigned_id" :options="$users" />
                            </div>
                            <x-slot:actions>
                                @can('delete crm tasks')
                                    <x-mary-button onclick="modalDeleteTaskItem{{ $task->id }}.showModal()" label="{{ ucfirst(__('laravel-crm::lang.delete')) }}" class="btn-error text-white" style="margin-inline-end: auto" type="button" />
                                @endcan
                                <x-mary-button wire:click="cancel" label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" type="button" />
                                @can('edit crm tasks')
                                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save_changes')) }}" class="btn-primary text-white" type="submit" spinner="update" />
                                @endcan
                            </x-slot:actions>
                        </x-mary-form>
                    @else
                        @if($task->description)
                            {{-- Typed into a plain textarea: escaped, with its line breaks kept. --}}
                            <div>{!! nl2br(e($task->description)) !!}</div>
                        @endif
                        <div class="flex flex-row gap-2">
                            @if($task->ownerUser)
                                <small>{{ ucfirst(__('laravel-crm::lang.requested_by')) }} <a href="{{ route('laravel-crm.users.show', $task->ownerUser) }}" class="link link-hover link-primary">{{ $task->ownerUser->name }}</a></small>
                            @endif
                            @if($task->ownerUser && $task->assignedToUser)
                                <small>|</small>
                            @endif
                            @if($task->assignedToUser)
                                <small>{{ ucfirst(__('laravel-crm::lang.assigned_to')) }} <a href="{{ route('laravel-crm.users.show', $task->assignedToUser) }}" class="link link-hover link-primary">{{ $task->assignedToUser->name }}</a></small>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </x-slot:content>
    </x-mary-collapse>

    {{-- Outside the widget: it clips what overflows it, and a click inside
         its heading would open or close the item. --}}
    <div class="absolute top-3 z-10" style="inset-inline-end: 3rem">
        <div class="flex items-center gap-2">
            @can('edit crm tasks')
                <x-mary-dropdown right>
                    <x-slot:trigger>
                        <x-mary-icon name="o-ellipsis-horizontal" />
                    </x-slot:trigger>
                    @can('edit crm tasks')
                        <x-mary-menu-item wire:click="edit" title="{{ ucfirst(__('laravel-crm::lang.edit')) }}" />
                        @if(! $completed_at)
                            <x-mary-menu-item wire:click="complete" title="{{ ucfirst(__('laravel-crm::lang.complete')) }}" />
                        @endif
                    @endcan
                </x-mary-dropdown>
            @endcan
        </div>
    </div>

    @can('delete crm tasks')
        @include('laravel-crm::livewire.partials.activity-delete-confirm', ['dialogId' => 'modalDeleteTaskItem'.$task->id, 'what' => 'task'])
    @endcan
</div>
