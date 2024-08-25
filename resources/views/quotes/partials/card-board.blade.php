@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ ucfirst(__('laravel-crm::lang.quotes')) }}
        @endslot

        @slot('actions') 
            @include('laravel-crm::partials.view-types', [
                'model' => 'quotes', 
            ])
            @include('laravel-crm::partials.filters', [
                'action' => route('laravel-crm.quotes.filter'),
                'model' => '\VentureDrake\LaravelCrm\Models\Quote'
            ])
            @can('create crm quotes')
               <a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.quotes.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_quote')) }}</a>
            @endcan
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-table')
        
        <livewire:live-quote-board :quotes="$quotes" />

    @endcomponent

@endcomponent
