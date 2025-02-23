
<div class="flex flex-col flex-shrink-0 w-80 card bg-base-300 rounded-lg py-4 px-5 shadow-sm">
    <div class="card-header bg-light">
        <h3 class="card-title h5 mb-4">
            {{ $stage['name'] }}
        </h3>
        @isset($stage['description'] )
            <small class="mb-0 text-muted">
                {{ $stage['description'] }}
            </small>
        @endisset
    </div>
    <div class="card-body p-0" id="{{ $stage['stageRecordsId'] }}" data-stage-id="{{ $stage['id'] }}">
     @foreach($stage['records'] as $record)
         @include('laravel-crm::livewire.kanban-board.record', [
             'record' => $record,
         ])
     @endforeach
    </div>
    <div class="card-footer">
        <a href="{{ url(route('laravel-crm.'.\Illuminate\Support\Str::plural($model).'.create', ['stage' => $stage['id']])) }}" class="btn btn-primary btn-block text-white mt-2">Add
            {{ $model }}</a>
    </div>
</div>



