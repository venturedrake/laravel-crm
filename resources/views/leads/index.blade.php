@extends('laravel-crm::layouts.app')

@section('content')

    @include('flash::message')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">Leads</h3> <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.leads.create')) }}"><span class="fa fa-plus"></span>  Add lead</a></span></div>
        <div class="card-body p-0 table-responsive">
            <table class="table mb-0">
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
                @foreach($leads as $lead)
                    <tr class="has-link" data-url="{{ url(route('laravel-crm.leads.show',$lead)) }}">
                        <td>{{ $lead->title }}</td>
                        <td>{{ $lead->person_name }}</td>
                        <td>{{ $lead->organisation_name }}</td>
                        <td>{{ $lead->amount }}</td>
                        <td>{{ $lead->created_at->diffForHumans() }}</td>
                        <td>{{ $lead->assignedToUser->name }}</td>
                        <td class="disable-link">
                            <a href="#" class="btn btn-success btn-sm">Convert</a>
                            <a href="#" class="btn btn-danger btn-sm"><span class="fa fa-trash-o" aria-hidden="true"></span></a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($leads instanceof \Illuminate\Pagination\LengthAwarePaginator )
            <div class="card-footer">
                {{ $leads->links() }}
            </div>
        @endif
        </div>
    </div>

@endsection