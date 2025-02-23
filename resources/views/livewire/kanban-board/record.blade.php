<x-mary-card class="mb-3 cursor-grab" id="{{ $record['id'] }}">
    <span>{{ $record['title'] }}</span>
    @if($record['labels'])
        <div class="mt-2">
        @foreach($record['labels'] as $label)
            <x-mary-badge value="{{ $label->name }}" class="text-white" style="border-color: #{{ $label->hex }}; background-color: #{{ $label->hex }}" />
        @endforeach
        </div>
    @endif
    <div class="mt-2">
        <a href="{{ url(route('laravel-crm.'.\Illuminate\Support\Str::plural($model).'.show', $record['id'])) }}" class="link link-hover link-primary">{{ $record['number'] }}</a>
    </div>
    {{--<x-slot:menu>
        <x-mary-button icon="o-share" class="btn-circle btn-sm" />
        <x-mary-icon name="o-heart" class="cursor-pointer" />
    </x-slot:menu>--}}
    <div class="flex justify-between mt-2">
        <div>
            @if($record['amount'])
                {{ money($record['amount'], $record['currency']) }}
            @endif
        </div>
        <div>
            <x-mary-icon name="fas.user-circle" />
        </div>
    </div>
</x-mary-card>

{{--
<div class="card mb-3 cursor-grab" id="{{ $record['id'] }}">
--}}
   {{-- <x-mary-card title="Your stats" subtitle="Our findings about you" shadow separator>
        I have title, subtitle, separator and shadow.
    </x-mary-card>--}}

    
    {{--<div class="card-body p-3 text-wrap">
        <h5 class="mb-1">{{ $record['title'] }}</h5>
        @foreach($record['labels'] as $label)
            <x-mary-badge value="{{ $label->name }}" class="text-white" style="border-color: #{{ $label->hex }}; background-color: #{{ $label->hex }}" />
        @endforeach
        --}}{{--@include('laravel-crm::partials.labels',[
            'labels' => $record['labels'],
            'limit' => 3
        ])--}}{{--
        <div class="mt-2">
            <a href="{{ url(route('laravel-crm.'.\Illuminate\Support\Str::plural($model).'.show', $record['id'])) }}">{{ $record['number'] }}</a>
        </div>
        <div class="mt-2">
            @if($record['amount'])
            {{ money($record['amount'], $record['currency']) }}
            @endif
            <div class="mb-0 d-inline-block float-right"><i class="fa fa-user-circle" aria-hidden="true"></i></div>
        </div>
    </div>--}}
{{--</div>--}}
