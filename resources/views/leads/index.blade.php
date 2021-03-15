@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">Leads</h3> <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.leads.create')) }}"><span class="fa fa-plus"></span>  Add lead</a></span></div>
        <div class="card-body p-0 table-responsive">
            <table class="table mb-0 card-table table-hover">
                <thead>
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col">Labels</th>
                    <th scope="col">Value</th>
                    <th scope="col">Organisation</th>
                    <th scope="col">Contact person</th>
                    <th scope="col">Created</th>
                    <th scope="col">Assigned To</th>
                    <th scope="col" width="210"></th>
                </tr>
                </thead>
                <tbody>
                @foreach($leads as $lead)
                    <tr class="has-link" data-url="{{ url(route('laravel-crm.leads.show',$lead)) }}">
                        <td>{{ $lead->title }}</td>
                        <td>@include('laravel-crm::partials.labels',[
                            'labels' => $lead->labels,
                            'limit' => 3
                        ])</td>
                        <td>{{ money($lead->amount, $lead->currency) }}</td>
                        <td>{{ $lead->organisation->name ?? $lead->organisation_name }}</td>
                        <td>{{ $lead->person->name ??  $lead->person_name }}</td>
                        <td>{{ $lead->created_at->diffForHumans() }}</td>
                        <td>{{ $lead->assignedToUser->name }}</td>
                        <td class="disable-link text-right">
                            <a href="{{  route('laravel-crm.leads.convert-to-deal',$lead) }}" class="btn btn-success btn-sm">Convert</a>
                            <a href="{{  route('laravel-crm.leads.show',$lead) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                            <a href="{{  route('laravel-crm.leads.edit',$lead) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                            <form action="{{ route('laravel-crm.leads.destroy',$lead) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                {{ method_field('DELETE') }}
                                {{ csrf_field() }}
                                <button class="btn btn-danger btn-sm" type="submit" data-model="lead"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                            </form>
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