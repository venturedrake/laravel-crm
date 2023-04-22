@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ $team->title }}
        @endslot

        @slot('actions')
            <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.teams.index')) }}"><span class="fa fa-angle-double-left"></span> {{ ucfirst(__('laravel-crm::lang.back_to_teams')) }}</a> | 
                <a href="{{ url(route('laravel-crm.teams.edit', $team)) }}" type="button" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                <form action="{{ route('laravel-crm.teams.destroy',$team) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.team') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
            </span>
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-body')

        <div class="row">
            <div class="col-sm-6 border-right">
                <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.details')) }}</h6>
                <hr />
                <dl class="row">
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.name')) }}</dt>
                    <dd class="col-sm-9">{{ $team->name }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.created_by')) }}</dt>
                    <dd class="col-sm-9">{{ $team->userCreated->name }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.created')) }}</dt>
                    <dd class="col-sm-9">{{ $team->created_at->format($dateFormat)}}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.updated')) }}</dt>
                    <dd class="col-sm-9">{{ $team->updated_at->format($dateFormat)}}</dd>
                </dl>
                <h6 class="text-uppercase mt-4 section-h6-title"><span>{{ ucfirst(__('laravel-crm::lang.users')) }} ({{ $team->users->count() }})</span></h6>
                <hr />
                @foreach($team->users as $user)
                    <p><span class="fa fa-user" aria-hidden="true"></span> {{ $user->name }}</p>
                @endforeach
            </div>
            <div class="col-sm-6">
                <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.activities')) }}</h6>
                <hr />
                ...
            </div>
        </div>
        
    @endcomponent

@endcomponent    