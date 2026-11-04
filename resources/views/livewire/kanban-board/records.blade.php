@foreach($records as $record)
    @include('laravel-crm::livewire.kanban-board.record', [
        'record' => $record,
    ])
@endforeach
