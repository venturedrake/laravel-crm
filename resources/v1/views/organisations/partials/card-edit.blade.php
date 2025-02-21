<form method="POST" action="{{ url(route('laravel-crm.organizations.update', $organization)) }}">
    @csrf
    @method('PUT')
    @component('laravel-crm::components.card')

        @component('laravel-crm::components.card-header')

            @slot('title')
                {{ ucfirst(__('laravel-crm::lang.edit_organization')) }}
            @endslot

            @slot('actions')
                @include('laravel-crm::partials.return-button',[
                    'model' => $organization,
                    'route' => 'organizations'
                ])
            @endslot

        @endcomponent

        @component('laravel-crm::components.card-body')

            @include('laravel-crm::organizations.partials.fields')

        @endcomponent

        @component('laravel-crm::components.card-footer')
            <a href="{{ url(route('laravel-crm.organizations.index')) }}" class="btn btn-outline-secondary">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</a>
            <button type="submit" class="btn btn-primary">{{ ucwords(__('laravel-crm::lang.save_changes')) }}</button>
        @endcomponent

    @endcomponent
</form>