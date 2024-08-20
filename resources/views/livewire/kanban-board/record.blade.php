<div class="card mb-3 cursor-grab" id="{{ $record['id'] }}">
    <div class="card-body p-3 text-wrap">
        <p class="mb-0">{{ $record['title'] }}</p>
        @include('laravel-crm::partials.labels',[
            'labels' => $record['labels'],
            'limit' => 3
        ])
        <div class="mt-2">
            <a href="{{ url(route('laravel-crm.'.\Illuminate\Support\Str::plural($model).'.show', $record['id'])) }}">LD-{{ $record['id'] }}</a> 
            <div class="mb-0 d-inline-block float-right"><i class="fa fa-user-circle" aria-hidden="true"></i></div>
        </div>
    </div>
</div>
