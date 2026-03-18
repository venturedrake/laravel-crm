<div class="card bg-base-100 rounded-lg p-5 mb-3 cursor-grab" id="{{ $record['id'] }}">
    <div class="grow-1 null">
        <div class="flex justify-between">
            <div>
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
            </div>
            <div class="ml-2">
                @switch($model)
                    @case('lead')
                    <x-mary-dropdown  class="btn-xs btn-square" right>
                        <x-mary-menu-item link="{{ route('laravel-crm.deals.create', ['model' => 'lead', 'id' => $record['id']]) }}" title="{{ ucfirst(__('laravel-crm::lang.convert')) }}" />
                        <x-mary-menu-item link="{{ route('laravel-crm.leads.show', ['lead' => $record['id']]) }}" title="{{ ucfirst(__('laravel-crm::lang.view')) }}" />
                        <x-mary-menu-item link="{{ route('laravel-crm.leads.edit', ['lead' => $record['id']]) }}" title="{{ ucfirst(__('laravel-crm::lang.edit')) }}" />
                        <x-mary-menu-item onclick="modalDeleteLead{{ $record['id'] }}.showModal()" title="{{ ucfirst(__('laravel-crm::lang.delete')) }}" />
                    </x-mary-dropdown>
                    <x-crm-delete-confirm model="lead" id="{{ $record['id'] }}" />
                    @break
                @endswitch
            </div>
        </div>
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
    </div>
</div>

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
