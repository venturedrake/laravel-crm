<form method="POST" action="{{ url(route('laravel-crm.organisations.store')) }}">
    @csrf
    @component('laravel-crm::components.card')

        @component('laravel-crm::components.card-header')

            @slot('title')
                Create organisation
            @endslot

            @slot('actions')
                <span class="float-right"><a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.organisations.index')) }}"><span class="fa fa-angle-double-left"></span> Back to organisations</a></span>
            @endslot

        @endcomponent

        @component('laravel-crm::components.card-body')

            @include('laravel-crm::organisations.partials.fields')

        @endcomponent

        @component('laravel-crm::components.card-footer')
            <a href="{{ url(route('laravel-crm.organisations.index')) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save</button>
        @endcomponent

    @endcomponent
</form>