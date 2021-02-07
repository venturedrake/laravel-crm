@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">Teams</h3> <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.teams.create')) }}"><span class="fa fa-plus"></span> Add team</a></span></div>
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
                @foreach($teams as $team)
                    <tr class="has-link" data-url="{{ url(route('laravel-crm.teams.show',$team)) }}">
                        <td>{{ $team->title }}</td>
                        <td>{{ $team->team_name }}</td>
                        <td>{{ $team->team_name }}</td>
                        <td>{{ money($team->amount, $team->currency) }}</td>
                        <td>{{ $team->created_at->diffForHumans() }}</td>
                        <td>{{ $team->assignedToUser->name }}</td>
                        <td class="disable-link text-right">
                            <a href="#" class="btn btn-success btn-sm">Convert</a>
                            <form action="{{ route('laravel-crm.teams.destroy',$team) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                {{ method_field('DELETE') }}
                                {{ csrf_field() }}
                                <button class="btn btn-danger btn-sm" type="submit" data-model="team"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($teams instanceof \Illuminate\Pagination\LengthAwarePaginator )
            <div class="card-footer">
                {{ $teams->links() }}
            </div>
        @endif
        </div>
    </div>

@endsection