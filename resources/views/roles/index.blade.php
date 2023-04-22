@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            @include('laravel-crm::layouts.partials.nav-settings')
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane active" id="roles" role="tabpanel">
                    <h3 class="mb-3"> {{ ucfirst(__('laravel-crm::lang.roles')) }}  @can('create crm roles')<span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.roles.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_role')) }}</a></span>@endcan</h3>
                    <div class="table-responsive">
                        <table class="table mb-0 card-table table-hover">
                            <thead>
                            <tr>
                                <th scope="col">{{ ucfirst(__('laravel-crm::lang.name')) }}</th>
                                <th scope="col">{{ ucfirst(__('laravel-crm::lang.created')) }}</th>
                                <th scope="col">{{ ucfirst(__('laravel-crm::lang.updated')) }}</th>
                                <th scope="col">{{ ucfirst(__('laravel-crm::lang.users')) }}</th>
                                <th scope="col"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($roles as $role)
                                <tr class="has-link" data-url="{{ url(route('laravel-crm.roles.show',$role)) }}">
                                    <td>{{ $role->name }}
                                    @if($role->description)
                                        <br /><small>{{ $role->description }}</small>
                                    @endif    
                                    </td>
                                    <td>{{ $role->created_at->format($dateFormat) }}</td>
                                    <td>{{ $role->updated_at->format($dateFormat) }}</td>
                                    <td>{{ $role->users->count() }}</td>
                                    <td class="disable-link text-right">
                                        @can('view crm roles')
                                        <a href="{{  route('laravel-crm.roles.show',$role) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                                        @endcan
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