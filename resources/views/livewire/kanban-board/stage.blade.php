
<div class="flex flex-col shrink-0 w-80 card bg-base-300 rounded-lg py-4 px-5 shadow-xs" wire:key="stage-{{ $stage['id'] }}" data-board-stage="{{ $stage['id'] }}">
    <div class="card-header bg-light mb-4">
        <h3 class="card-title h5">
            {{-- One element: card-title is a flex container, so loose text and a span would space apart. --}}
            <span>{{ $stage['name'] }} (<span data-board-count>{{ $stage['count'] }}</span>)</span>
        </h3>
        {{-- wire:replace here and below: the client changes both, so there is nothing for a morph to line up with. --}}
        <div class="text-sm text-base-content/60" data-board-totals wire:replace>
            @foreach($stage['totals'] as $total)
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
        <button type="button"
                class="btn btn-sm btn-ghost btn-block mb-3 {{ $stage['hasMore'] ? '' : 'hidden' }}"
                data-board-more
                wire:click="loadMore({{ $stage['id'] }})"
                wire:loading.attr="disabled"
                wire:target="loadMore({{ $stage['id'] }})">
            <span wire:loading wire:target="loadMore({{ $stage['id'] }})" class="loading loading-spinner loading-xs"></span>
            {{ ucfirst(__('laravel-crm::lang.load_more')) }}
        </button>
        @can('create crm '.\Illuminate\Support\Str::plural($model))
            <div class="card-footer">
                 <a href="{{ url(route('laravel-crm.'.\Illuminate\Support\Str::plural($model).'.create', ['stage' => $stage['id']])) }}" class="btn btn-primary btn-block text-white">Add
                     {{ $model }}
                 </a>
            </div>
        @endcan    
    </div>
</div>



