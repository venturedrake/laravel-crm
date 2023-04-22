@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ $user->name }}
        @endslot

        @slot('actions')
            <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.users.index')) }}"><span class="fa fa-angle-double-left"></span> {{ ucfirst(__('laravel-crm::lang.back_to_users')) }}</a> | 
                @can('edit crm users')
                <a href="{{ url(route('laravel-crm.users.edit', $user)) }}" type="button" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                @endcan
                @can('delete crm users')
                <form action="{{ route('laravel-crm.users.destroy',$user) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                     {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.user') }}" {{ (auth()->user()->id == $user->id) ? 'disabled' : null }}><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
                @endcan    
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
                    <dd class="col-sm-9">{{ $user->name }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.email')) }}</dt>
                    <dd class="col-sm-9">{{ $user->email }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.email_verified')) }}</dt>
                    <dd class="col-sm-9">{{ ($user->email_verified_at) ? $user->email_verified_at->format($dateFormat.' '.$timeFormat) : null }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.CRM_Access')) }}</dt>
                    <dd class="col-sm-9">{{ ($user->crm_access) ? 'Yes' : 'No' }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.CRM_Role')) }}</dt>
                    <dd class="col-sm-9">{{ $user->roles()->first()->name ?? null }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.created')) }}</dt>
                    <dd class="col-sm-9">{{ $user->created_at->format($dateFormat) }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.last_online')) }}</dt>
                    <dd class="col-sm-9">{{ ($user->last_online_at) ?  \Carbon\Carbon::parse($user->last_online_at)->diffForHumans() :  'Never' }}</dd>
                </dl>
            </div>
            <div class="col-sm-6">
                @can('view crm teams')
                    <h6 class="text-uppercase section-h6-title"><span>{{ ucfirst(__('laravel-crm::lang.teams')) }} ({{ $user->crmTeams->count() }})</span></h6>
                    <hr />
                    @foreach($user->crmTeams as $team)
                        <p><span class="fa fa-users" aria-hidden="true"></span> {{ $team->name }}</p>
                    @endforeach
                @endcan
            </div>
        </div>
        
    @endcomponent

@endcomponent    