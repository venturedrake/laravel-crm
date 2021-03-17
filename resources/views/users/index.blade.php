@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">Users</h3> 
            <span class="float-right">
                <a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.users.create')) }}"><span class="fa fa-plus"></span> Add user</a>
                <a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.users.invite')) }}"><span class="fa fa-paper-plane"></span> Invite user</a>
            </span>
        </div>
        
        <div class="card-body p-0 table-responsive">
            <table class="table mb-0 card-table table-hover">
                <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Email Verified</th>
                    <th scope="col">Created</th>
                    <th scope="col">Updated</th>
                    <th scope="col">Last Online</th>
                    <th scope="col">CRM Access</th>
                    <th scope="col" width="150"></th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr class="has-link" data-url="{{ url(route('laravel-crm.users.show',$user)) }}">
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ ($user->email_verified_at) ? $user->email_verified_at->toDayDateTimeString() : null }}</td>
                        <td>{{ $user->created_at->toFormattedDateString() }}</td>
                        <td>{{ $user->updated_at->toFormattedDateString() }}</td>
                        <td>{{ ($user->last_online_at) ?  \Carbon\Carbon::parse($user->last_online_at)->diffForHumans() :  'Never' }}</td>
                        <td>{{ ($user->crm_access) ? 'Yes' : 'No' }}</td>
                        <td class="disable-link text-right">
                            <a href="{{  route('laravel-crm.users.show',$user) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                            <a href="{{  route('laravel-crm.users.edit',$user) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                            <form action="{{ route('laravel-crm.users.destroy',$user) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                {{ method_field('DELETE') }}
                                {{ csrf_field() }}
                                <button class="btn btn-danger btn-sm" type="submit" data-model="user" {{ (auth()->user()->id == $user->id) ? 'disabled' : null }}><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($users instanceof \Illuminate\Pagination\LengthAwarePaginator )
            <div class="card-footer">
                {{ $users->links() }}
            </div>
        @endif
        </div>
    </div>

@endsection