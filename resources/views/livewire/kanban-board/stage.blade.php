
<div class="col-sm-6 col-md-4 col-xl-3">
    <div class="card mb-3">
        <div class="card-header bg-light">
            <h3 class="card-title h5 mb-1">
                {{ $stage['name'] }}
            </h3>
            @isset($stage['description'] )
            <small class="mb-0 text-muted">
                {{ $stage['description'] }}
            </small>
            @endisset   
        </div>
        <div class="card-body pb-1" id="{{ $stage['stageRecordsId'] }}" data-stage-id="{{ $stage['id'] }}">
            @foreach($stage['records'] as $record)
                @include('laravel-crm::livewire.kanban-board.record', [
                    'record' => $record,
                ])
            @endforeach
        </div>
        <div class="card-footer">
            <a href="{{ url(route('laravel-crm.'.\Illuminate\Support\Str::plural($model).'.create', ['stage' => $stage['id']])) }}" class="btn btn-primary btn-block">Add
                {{ $model }}</a>
        </div>
    </div>
</div>



