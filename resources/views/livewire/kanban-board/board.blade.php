<div>
    <div class="container-fluid mt-3">
        <div wire:ignore class="row flex-row flex-sm-nowrap py-3">
            @foreach($stages as $stage)
                @include('laravel-crm::livewire.kanban-board.stage', [
                    'stage' => $stage
                ])
            @endforeach
        </div>
    </div>

    <div wire:ignore>
        @includeWhen($sortable, 'laravel-crm::livewire.kanban-board.sortable', [
            'sortable' => $sortable,
            'sortableBetweenStages' => $sortableBetweenStages,
        ])
    </div>

    {{-- A quote card's Send item talks to this one shared drawer. --}}
    @if($model === 'quote')
        <livewire:crm-quote-send type="menu-item" />
    @endif
</div>
