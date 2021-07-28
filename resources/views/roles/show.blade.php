@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            @include('laravel-crm::layouts.partials.nav-settings')
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane active" id="roles" role="tabpanel">
                    <h3 class="mb-3">{{ ucfirst(__('laravel-crm::lang.role')) }}: {{ $role->name }} <span class="float-right">
                            <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.roles.index')) }}"><span class="fa fa-angle-double-left"></span> {{ ucfirst(__('laravel-crm::lang.back_to_roles')) }}</a> |
                            @can('edit crm roles')
                            <a href="{{  route('laravel-crm.roles.edit',$role) }}" class="btn btn-outline-secondary btn-sm {{ (in_array($role->name, ['Owner','Admin'])) ? 'disabled' : null }}" ><span class="fa fa-edit" aria-hidden="true"></span></a>
                            @endcan
                            @can('delete crm roles')
                            <form action="{{ route('laravel-crm.roles.destroy',$role) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                {{ method_field('DELETE') }}
                                {{ csrf_field() }}
                                <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.role') }}" {{ (in_array($role->name, ['Owner','Admin']) || $role->users->count() >= 1) ? 'disabled' : null }}><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                            </form>
                            @endcan    
                        </span></h3>
                    <div class="row">
                        <div class="col-sm-6 border-right">
                            <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.details')) }}</h6>
                            <hr />
                            <dl class="row">
                                <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.description')) }}</dt>
                                <dd class="col-sm-9">{{ $role->description }}</dd>
                                <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.CRM_role')) }}</dt>
                                <dd class="col-sm-9">{{ ucfirst(__('laravel-crm::lang.yes')) }}</dd>
                            </dl>
                            <h6 class="text-uppercase mt-4">{{ ucfirst(__('laravel-crm::lang.permissions')) }}</h6>
                            <hr />
                            @foreach($role->permissions as $permission)
                                <span class="badge badge-secondary">{{ $permission->name }}</span>
                            @endforeach
                        </div>
                        <div class="col-sm-6">
                            @can('view crm users')
                            <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.users')) }}</h6>
                            <hr />
                            @foreach($role->users as $user)
                                <p><span class="fa fa-user" aria-hidden="true"></span> {{ $user->name }}</p>
                            @endforeach
                            @endcan    
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection