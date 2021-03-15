@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">People</h3> <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.people.create')) }}"><span class="fa fa-plus"></span>  Add person</a></span></div>
        <div class="card-body p-0 table-responsive">
            <table class="table mb-0 card-table table-hover">
                <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Label</th>
                    <th scope="col">Organisation</th>
                    <th scope="col">Email</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Open Deals</th>
                    <th scope="col">Lost Deals</th>
                    <th scope="col">Won Deals</th>
                    <th scope="col">Next Activity</th>
                    <th scope="col">Owner</th>
                    <th scope="col" width="150"></th>
                </tr>
                </thead>
                <tbody>
                @foreach($people as $person)
                    <tr class="has-link" data-url="{{ url(route('laravel-crm.people.show',$person)) }}">
                        <td>{{ $person->name }}</td>
                        <td>@include('laravel-crm::partials.labels',[
                            'labels' => $person->labels,
                            'limit' => 3
                        ])</td>
                        <td>{{ $person->organisation->name ?? null }}</td>
                        <td>{{ $person->getPrimaryEmail()->address ?? null }}</td>
                        <td>{{ $person->getPrimaryPhone()->number ?? null }}</td>
                        <td>{{ $person->deals->whereNull('closed_at')->count() }}</td>
                        <td>{{ $person->deals->where('closed_status', 'lost')->count() }}</td>
                        <td>{{ $person->deals->where('closed_status', 'won')->count() }}</td>
                        <td></td>
                        <td>{{ $person->ownerUser->name ?? null }}</td>
                        <td class="disable-link text-right">
                            <a href="{{  route('laravel-crm.people.show',$person) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                            <a href="{{  route('laravel-crm.people.edit',$person) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                            <form action="{{ route('laravel-crm.people.destroy',$person) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                {{ method_field('DELETE') }}
                                {{ csrf_field() }}
                                <button class="btn btn-danger btn-sm" type="submit" data-model="person"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($people instanceof \Illuminate\Pagination\LengthAwarePaginator )
            <div class="card-footer">
                {{ $people->links() }}
            </div>
        @endif
        </div>
    </div>

@endsection