@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">Organisations</h3> <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.organisations.create')) }}"><span class="fa fa-plus"></span>  Add organisation</a></span></div>
        <div class="card-body p-0 table-responsive">
            <table class="table mb-0 card-table table-hover">
                <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Label</th>
                    <th scope="col">Contact</th>
                    <th scope="col">Open Deals</th>
                    <th scope="col">Lost Deals</th>
                    <th scope="col">Won Deals</th>
                    <th scope="col">Next Activity</th>
                    <th scope="col">Owner</th>
                    <th scope="col" width="150"></th>
                </tr>
                </thead>
                <tbody>
                @foreach($organisations as $organisation)
                    <tr class="has-link" data-url="{{ url(route('laravel-crm.organisations.show',$organisation)) }}">
                        <td>{{ $organisation->name }}</td>
                        <td>@include('laravel-crm::partials.labels',[
                            'labels' => $organisation->labels,
                            'limit' => 3
                        ])</td>
                        <td></td>
                        <td>{{ $organisation->deals->whereNull('closed_at')->count() }}</td>
                        <td>{{ $organisation->deals->where('closed_status', 'lost')->count() }}</td>
                        <td>{{ $organisation->deals->where('closed_status', 'won')->count() }}</td>
                        <td></td>
                        <td>{{ $organisation->ownerUser->name ?? null }}</td>
                        <td class="disable-link text-right">
                            <a href="{{  route('laravel-crm.organisations.show',$organisation) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                            <a href="{{  route('laravel-crm.organisations.edit',$organisation) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                            <form action="{{ route('laravel-crm.organisations.destroy',$organisation) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                {{ method_field('DELETE') }}
                                {{ csrf_field() }}
                                <button class="btn btn-danger btn-sm" type="submit" data-model="organisation"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($organisations instanceof \Illuminate\Pagination\LengthAwarePaginator )
            <div class="card-footer">
                {{ $organisations->links() }}
            </div>
        @endif
        </div>
    </div>

@endsection