<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.features')) }}" class="mb-5" progress-indicator>
        <x-slot:middle class="justify-end!">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.features')) }}..." wire:model.live.debounce="search" icon="o-magnifying-glass" class="input-neutral" clearable />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button label="List" link="{{ url(route('laravel-crm.features.index')) }}" icon="o-list-bullet" class="btn" responsive />
            @can('create crm features')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.submit_feature')) }}" link="{{ url(route('laravel-crm.features.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    <div class="flex grow mt-4 space-x-6 overflow-auto">
        @foreach($stages as $stage)
            <div class="flex flex-col shrink-0 w-80 card bg-base-300 rounded-lg py-4 px-5 shadow-xs">
                <div class="card-header">
                    <h3 class="card-title h5 mb-4">
                        <span class="inline-block w-3 h-3 rounded-full align-middle mr-2" style="background-color: {{ $stage['color'] ?? '#6c757d' }}"></span>
                        {{ $stage['name'] }} ({{ count($stage['records']) }})
                    </h3>
                </div>
                <div class="card-body p-0">
                    <span id="{{ $stage['stageRecordsId'] }}" data-stage-id="{{ $stage['id'] }}">
                        @foreach($stage['records'] as $record)
                            <div class="card bg-base-100 rounded-lg p-5 mb-3 cursor-grab" id="{{ $record['id'] }}">
                                <div class="flex justify-between">
                                    <div>
                                        <span>{{ $record['title'] }}</span>
                                        <div class="mt-2">
                                            <a href="{{ url(route('laravel-crm.features.show', $record['id'])) }}" class="link link-hover link-primary">{{ $record['number'] }}</a>
                                        </div>
                                    </div>
                                    <div class="ml-2">
                                        @canany(['view crm features', 'edit crm features', 'delete crm features'])
                                            <x-mary-dropdown class="btn-xs btn-square" right>
                                                @can('view crm features')
                                                    <x-mary-menu-item link="{{ route('laravel-crm.features.show', ['feature' => $record['id']]) }}" title="{{ ucfirst(__('laravel-crm::lang.view')) }}" />
                                                @endcan
                                                @can('edit crm features')
                                                    <x-mary-menu-item link="{{ route('laravel-crm.features.edit', ['feature' => $record['id']]) }}" title="{{ ucfirst(__('laravel-crm::lang.edit')) }}" />
                                                @endcan
                                                @can('delete crm features')
                                                    <x-mary-menu-item wire:click="delete({{ $record['id'] }})" title="{{ ucfirst(__('laravel-crm::lang.delete')) }}" />
                                                @endcan
                                            </x-mary-dropdown>
                                        @endcanany
                                    </div>
                                </div>
                                <div class="flex justify-between mt-2 text-xs">
                                    <div title="{{ ucfirst(__('laravel-crm::lang.votes')) }}"><x-mary-icon name="o-hand-thumb-up" class="w-4 h-4 inline" /> {{ $record['votes_count'] }}</div>
                                    <div title="{{ ucfirst(__('laravel-crm::lang.comments')) }}"><x-mary-icon name="o-chat-bubble-left" class="w-4 h-4 inline" /> {{ $record['comments_count'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <div wire:ignore>
        @includeWhen($sortable, 'laravel-crm::livewire.kanban-board.sortable', [
            'sortable' => $sortable,
            'sortableBetweenStages' => $sortableBetweenStages,
        ])
    </div>
</div>
