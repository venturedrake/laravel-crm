<div class="crm-content">
    {{-- HEADER --}}
    <x-crm-header title="{{ $task->name }}" class="mb-5" progress-indicator>
        {{-- BADGES --}}
        <x-slot:badges>
            @if($task->completed_at)
                <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.completed')) }}" class="badge badge-success text-white" />
            @else
                <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.pending')) }}" class="badge badge-neutral" />
            @endif
        </x-slot:badges>

        {{-- ACTIONS --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_tasks')) }}" link="{{ url(route('laravel-crm.tasks.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive />
            @can('edit crm tasks')
                @if(! $task->completed_at)
                    | <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.complete')) }}" wire:click="complete" class="btn-sm btn-success text-white" spinner="complete" responsive />
                @endif
                <x-mary-button link="{{ url(route('laravel-crm.tasks.edit', $task)) }}" icon="o-pencil-square" class="btn-sm btn-square btn-outline" responsive />
            @endcan
            @can('delete crm tasks')
                <x-mary-button onclick="modalDeleteTask{{ $task->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                <x-crm-delete-confirm model="task" id="{{ $task->id }}" />
            @endcan
        </x-slot:actions>
    </x-crm-header>

    <div class="grid gap-y-5">
        <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
            <div class="grid gap-y-3">
                <div class="flex flex-row gap-5">
                    <strong>{{ ucfirst(__('laravel-crm::lang.created')) }}</strong>
                    <span>{{ $task->created_at->diffForHumans() }}</span>
                </div>
                @if($task->due_at)
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.due_date')) }}</strong>
                        <span>{{ $task->due_at->diffForHumans() }}</span>
                    </div>
                @endif
                @if($task->completed_at)
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.completed')) }}</strong>
                        <span>{{ $task->completed_at->diffForHumans() }}</span>
                    </div>
                @endif
                <div class="flex flex-row gap-5">
                    <strong>{{ ucfirst(__('laravel-crm::lang.description')) }}</strong>
                    <span>{{ $task->description }}</span>
                </div>
                <div class="flex flex-row gap-5">
                    <strong>{{ ucfirst(__('laravel-crm::lang.owner')) }}</strong>
                    <span>
                            @if($task->ownerUser)
                            <a href="{{ route('laravel-crm.users.show', $task->ownerUser) }}" class="link link-hover link-primary">{{ $task->ownerUser->name }}</a>
                        @else
                            {{ ucfirst(__('laravel-crm::lang.unallocated')) }}
                        @endif
                        </span>
                </div>
                <div class="flex flex-row gap-5">
                    <strong>{{ ucfirst(__('laravel-crm::lang.assigned_to')) }}</strong>
                    <span>
                            @if($task->assignedToUser)
                            <a href="{{ route('laravel-crm.users.show', $task->assignedToUser) }}" class="link link-hover link-primary">{{ $task->assignedToUser->name }}</a>
                        @else
                            {{ ucfirst(__('laravel-crm::lang.unallocated')) }}
                        @endif
                        </span>
                </div>
            </div>
        </x-mary-card>
    </div>
</div>

