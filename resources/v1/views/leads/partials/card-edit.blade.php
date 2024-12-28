<form method="POST" action="{{ url(route('laravel-crm.leads.update', $lead)) }}">
    @csrf
    @method('PUT')
    @component('laravel-crm::components.card')

        @component('laravel-crm::components.card-header')

            @slot('title')
                {{ ucfirst(__('laravel-crm::lang.edit_lead')) }}
            @endslot

            @slot('actions')
                @include('laravel-crm::partials.return-button',[
                   'model' => $lead,
                   'route' => 'leads'
               ])
            @endslot

        @endcomponent

        @component('laravel-crm::components.card-body')

            @include('laravel-crm::leads.partials.fields', [
                'generateTitle' => false
            ])

        @endcomponent

        @component('laravel-crm::components.card-footer')
            <a href="{{ url(route('laravel-crm.leads.index')) }}" class="btn btn-outline-secondary">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</a>
            <button type="submit" class="btn btn-primary">{{ ucwords(__('laravel-crm::lang.save_changes')) }}</button>
        @endcomponent

    @endcomponent
</form>