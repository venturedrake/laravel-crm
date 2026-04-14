<div class="crm-content">
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.tasks')) }}" progress-indicator>
        {{-- SEARCH --}}
        <x-slot:middle class="justify-end!">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.search_tasks')) }}..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:middle>

        {{-- ACTIONS --}}
        <x-slot:actions>
            <x-mary-button label="Filters"
                           icon="o-funnel"
                           :badge="$filterCount ?? 0"
                           badge-classes="font-mono badge-primary badge-soft"
                           @click="$wire.showFilters = true"
                           responsive />

            @can('create crm tasks')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_task')) }}" link="{{ url(route('laravel-crm.tasks.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    {{-- TABLE --}}
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$tasks" :link="route('laravel-crm.tasks.show', ['task' => '[id]'])" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('cell_completed_at', $task)
                @if($task->completed_at)
                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.completed')) }}" class="badge-success text-white" />
                @else
                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.pending')) }}" class="badge-neutral" />
                @endif
            @endscope
            @scope('actions', $task)
                <div class="flex gap-1 justify-end">
                    @can('edit crm tasks')
                        @if(! $task->completed_at)
                            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.complete')) }}" wire:click="complete({{ $task->id }})" class="btn-sm btn-success text-white" spinner />
                        @endif
                    @endcan
                    @can('view crm tasks')
                    <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.tasks.show', $task)) }}" class="btn-sm btn-square btn-outline" />
                    @endcan    
                    @can('edit crm tasks')
                        <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.tasks.edit', $task)) }}" class="btn-sm btn-square btn-outline" />
                    @endcan
                    @can('delete crm tasks')
                        <x-mary-button onclick="modalDeleteTask{{ $task->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                        <x-crm-delete-confirm model="task" id="{{ $task->id }}" />
                    @endcan
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- FILTERS --}}
    <x-mary-drawer wire:model="showFilters" title="Filters" class="lg:w-1/3" right separator with-close-button>
        <div class="grid gap-5" @keydown.enter="$wire.showFilters = false">
            <x-mary-choices label="{{ ucfirst(__('laravel-crm::lang.assigned_to')) }}" wire:model.live="user_id" :options="$users" icon="o-user" inline allow-all />
            <x-mary-select label="Status" wire:model.live="status" :options="[
                ['id' => '', 'name' => 'All'],
                ['id' => 'pending', 'name' => ucfirst(__('laravel-crm::lang.pending'))],
                ['id' => 'completed', 'name' => ucfirst(__('laravel-crm::lang.completed'))],
            ]" />
        </div>

        {{-- ACTIONS --}}
        <x-slot:actions>
            <x-mary-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-mary-button label="Done" icon="o-check" class="btn-primary text-white" @click="$wire.showFilters = false" />
        </x-slot:actions>
    </x-mary-drawer>
</div>

