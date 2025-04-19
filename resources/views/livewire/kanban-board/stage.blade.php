
<div class="flex flex-col shrink-0 w-80 card bg-base-300 rounded-lg py-4 px-5 shadow-xs">
    <div class="card-header bg-light">
        <h3 class="card-title h5 mb-4">
            {{ $stage['name'] }} ({{ count($stage['records']) }})
        </h3>
        @isset($stage['description'] )
            <small class="mb-0 text-muted">
                {{ $stage['description'] }}
            </small>
        @endisset
    </div>
    <div class="card-body p-0">
        <span id="{{ $stage['stageRecordsId'] }}" data-stage-id="{{ $stage['id'] }}">
            @foreach($stage['records'] as $record)
                @include('laravel-crm::livewire.kanban-board.record', [
                    'record' => $record,
                ])
            @endforeach
        </span>
        <div class="card-footer">
         <a href="{{ url(route('laravel-crm.'.\Illuminate\Support\Str::plural($model).'.create', ['stage' => $stage['id']])) }}" class="btn btn-primary btn-block text-white">Add
             {{ $model }}</a>
        </div>
    </div>
</div>



