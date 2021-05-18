<form method="POST" action="{{ url(route('laravel-crm.products.store')) }}">
    @csrf
    @component('laravel-crm::components.card')

        @component('laravel-crm::components.card-header')

            @slot('title')
                Create product
            @endslot

            @slot('actions')
                    <span class="float-right"><a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.products.index')) }}"><span class="fa fa-angle-double-left"></span> Back to products</a></span>
            @endslot

        @endcomponent

        @component('laravel-crm::components.card-body')
            
                @include('laravel-crm::products.partials.fields')
            
        @endcomponent
        
        @component('laravel-crm::components.card-footer')
                <a href="{{ url(route('laravel-crm.products.index')) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save</button>
        @endcomponent
        
    @endcomponent
</form>