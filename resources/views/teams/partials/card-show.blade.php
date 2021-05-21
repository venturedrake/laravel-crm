@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ $team->title }}
        @endslot

        @slot('actions')
            <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.teams.index')) }}"><span class="fa fa-angle-double-left"></span> Back to teams</a> | 
                <a href="{{ url(route('laravel-crm.teams.edit', $team)) }}" type="button" class="btn btn-outline-secondary btn-sm">Edit</a>
                <form action="{{ route('laravel-crm.teams.destroy',$team) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="team"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
            </span>
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-body')

        <div class="row">
            <div class="col-sm-6 border-right">
                <h6 class="text-uppercase">Details</h6>
                <hr />
                <dl class="row">
                    <dt class="col-sm-3 text-right">Name</dt>
                    <dd class="col-sm-9">{{ $team->name }}</dd>
                </dl>
                <h6 class="text-uppercase mt-4 section-h6-title"><span>Users ({{ $team->users->count() }})</span></h6>
                <hr />
                @foreach($team->users as $user)
                    <p><span class="fa fa-user" aria-hidden="true"></span> {{ $user->name }}</p>
                @endforeach
            </div>
            <div class="col-sm-6">
                <h6 class="text-uppercase">Activities</h6>
                <hr />
                ...
            </div>
        </div>
        
    @endcomponent

@endcomponent    