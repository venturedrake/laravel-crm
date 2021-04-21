<form method="POST" action="{{ url(route('laravel-crm.people.update', $person)) }}">
    @csrf
    @method('PUT')
    @component('laravel-crm::components.card')

        @component('laravel-crm::components.card-header')

            @slot('title')
                Edit person
            @endslot

            @slot('actions')
                    <span class="float-right"><a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.people.index')) }}"><span class="fa fa-angle-double-left"></span> Back to people</a></span>
            @endslot

        @endcomponent

        @component('laravel-crm::components.card-body')

            @include('laravel-crm::people.partials.fields')

        @endcomponent

        @component('laravel-crm::components.card-footer')
                <a href="{{ url(route('laravel-crm.people.index')) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
        @endcomponent

    @endcomponent
</form>