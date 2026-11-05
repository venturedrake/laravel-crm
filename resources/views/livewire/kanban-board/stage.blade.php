
<div class="flex flex-col shrink-0 w-80 card bg-base-300 rounded-lg py-4 px-5 shadow-xs" wire:key="stage-{{ $stage['id'] }}" data-board-stage="{{ $stage['id'] }}">
    <div class="card-header bg-light mb-4">
        <h3 class="card-title h5">
            {{-- One element: card-title is a flex container, so loose text and a span would space apart. --}}
            <span>{{ $stage['name'] }} (<span data-board-count>{{ $stage['count'] ?? count($stage['records']) }}</span>)</span>
        </h3>
        {{-- A board that isn't paged, like a host's, supplies neither these nor the count above. --}}
        {{-- wire:replace here and below: the client changes both, so a render replaces them rather than morphing around those changes. --}}
        <div class="text-sm text-base-content/60" data-board-totals wire:replace>
            @foreach($stage['totals'] ?? [] as $total)
                <div>{{ $total }}</div>
            @endforeach
        </div>
    </div>
    <div class="card-body p-0">
        <span id="{{ $stage['stageRecordsId'] }}" data-stage-id="{{ $stage['id'] }}" wire:replace>
            @include('laravel-crm::livewire.kanban-board.records', [
                'records' => $stage['records'],
            ])
        </span>
        {{-- Outside the sortable container, so it is never dragged or counted as a card. --}}
        {{-- A named spinner target, because a bare spinner gives every column the same wire:key. --}}
        <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.load_more')) }}"
                       class="btn-sm btn-ghost btn-block mb-3 {{ ($stage['hasMore'] ?? false) ? '' : 'hidden' }}"
                       data-board-more
                       wire:click="loadMore({{ $stage['id'] }})"
                       spinner="loadMore({{ $stage['id'] }})" />
        @can('create crm '.\Illuminate\Support\Str::plural($model))
            <div class="card-footer">
                 <a href="{{ url(route('laravel-crm.'.\Illuminate\Support\Str::plural($model).'.create', ['stage' => $stage['id']])) }}" class="btn btn-primary btn-block text-white">Add
                     {{ $model }}
                 </a>
            </div>
        @endcan    
    </div>
</div>



