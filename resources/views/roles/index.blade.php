@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            @include('laravel-crm::layouts.partials.nav-settings')
        </div>
        <div class="card-body">
            <div class="tab-content mt-3">
                <div class="tab-pane active" id="roles" role="tabpanel">
                    <h3 class="mb-3">Roles  <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.roles.create')) }}"><span class="fa fa-plus"></span> Add role</a></span></h3>
                    <div class="table-responsive">
                        <table class="table mb-0 card-table table-hover">
                            <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Created</th>
                                <th scope="col">Updated</th>
                                <th scope="col">Users</th>
                                <th scope="col"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($roles as $role)
                                <tr class="has-link" data-url="{{ url(route('laravel-crm.roles.show',$role)) }}">
                                    <td>{{ $role->name }}</td>
                                    <td>{{ $role->created_at->toFormattedDateString() }}</td>
                                    <td>{{ $role->updated_at->toFormattedDateString() }}</td>
                                    <td>{{ $role->users->count() }}</td>
                                    <td class="disable-link text-right">
                                        <a href="{{  route('laravel-crm.roles.show',$role) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                                        <a href="{{  route('laravel-crm.roles.edit',$role) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                                        <form action="{{ route('laravel-crm.roles.destroy',$role) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                            {{ method_field('DELETE') }}
                                            {{ csrf_field() }}
                                            <button class="btn btn-danger btn-sm" type="submit" data-model="role"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table> 
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection