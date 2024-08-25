@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ ucfirst(__('laravel-crm::lang.deals')) }}
        @endslot

        @slot('actions') 
            @include('laravel-crm::partials.view-types', [
                'model' => 'deals', 
            ])
            @include('laravel-crm::partials.filters', [
                'action' => route('laravel-crm.deals.filter'),
                'model' => '\VentureDrake\LaravelCrm\Models\Deal'
            ])
            @can('create crm deals')
               <a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.deals.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_deal')) }}</a>
            @endcan
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-table')
        
        <livewire:live-deal-board :deals="$deals" />

    @endcomponent

@endcomponent
