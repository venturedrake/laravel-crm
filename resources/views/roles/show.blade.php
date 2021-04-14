@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            @include('laravel-crm::layouts.partials.nav-settings')
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane active" id="roles" role="tabpanel">
                    <h3 class="mb-3">Role: {{ $role->name }} <span class="float-right">
                            <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.roles.index')) }}"><span class="fa fa-angle-double-left"></span> Back to roles</a> |
                            <a href="{{  route('laravel-crm.roles.edit',$role) }}" class="btn btn-outline-secondary btn-sm {{ (in_array($role->name, ['Owner','Admin'])) ? 'disabled' : null }}" ><span class="fa fa-edit" aria-hidden="true"></span></a>
                            <form action="{{ route('laravel-crm.roles.destroy',$role) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                {{ method_field('DELETE') }}
                                {{ csrf_field() }}
                                <button class="btn btn-danger btn-sm" type="submit" data-model="role" {{ (in_array($role->name, ['Owner','Admin']) || $role->users->count() >= 1) ? 'disabled' : null }}><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                            </form>
                        </span></h3>
                    <div class="row">
                        <div class="col-sm-6 border-right">
                            <h6 class="text-uppercase">Details</h6>
                            <dl class="row">
                                <dt class="col-sm-3 text-right">Description</dt>
                                <dd class="col-sm-9">{{ $role->description }}</dd>
                                <dt class="col-sm-3 text-right">CRM Role</dt>
                                <dd class="col-sm-9">Yes</dd>
                            </dl>
                            <h6 class="text-uppercase mt-4">Permissions</h6>
                            <hr />
                            @foreach($role->permissions as $permission)
                                <span class="badge badge-secondary">{{ $permission->name }}</span>
                            @endforeach
                        </div>
                        <div class="col-sm-6">
                            <h6 class="text-uppercase">Users</h6>
                            <hr />
                            @foreach($role->users as $user)
                                <p><span class="fa fa-user" aria-hidden="true"></span> {{ $user->name }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection