@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">Users</h3> <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.users.create')) }}"><span class="fa fa-plus"></span> Add user</a></span></div>
        <div class="card-body p-0 table-responsive">
            <table class="table mb-0 card-table table-hover">
                <thead>
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col">Contact</th>
                    <th scope="col">Organisation</th>
                    <th scope="col">Value</th>
                    <th scope="col">Created</th>
                    <th scope="col">Assigned To</th>
                    <th scope="col"></th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr class="has-link" data-url="{{ url(route('laravel-crm.users.show',$user)) }}">
                        <td>{{ $user->title }}</td>
                        <td>{{ $user->user_name }}</td>
                        <td>{{ $user->user_name }}</td>
                        <td>{{ money($user->amount, $user->currency) }}</td>
                        <td>{{ $user->created_at->diffForHumans() }}</td>
                        <td>{{ $user->assignedToUser->name }}</td>
                        <td class="disable-link text-right">
                            <a href="#" class="btn btn-success btn-sm">Convert</a>
                            <form action="{{ route('laravel-crm.users.destroy',$user) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                {{ method_field('DELETE') }}
                                {{ csrf_field() }}
                                <button class="btn btn-danger btn-sm" type="submit" data-model="user"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
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