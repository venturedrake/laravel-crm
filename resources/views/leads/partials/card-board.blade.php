@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ ucfirst(__('laravel-crm::lang.leads')) }}
        @endslot

        @slot('actions') 
            @include('laravel-crm::partials.view-types', [
                'model' => 'leads', 
            ])
            @include('laravel-crm::partials.filters', [
                'action' => route('laravel-crm.leads.filter'),
                'model' => '\VentureDrake\LaravelCrm\Models\Lead'
            ])
            @can('create crm leads')
               <a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.leads.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_lead')) }}</a>
            @endcan
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-table')

        <livewire:live-lead-board :leads="$leads" />

    @endcomponent

@endcomponent
