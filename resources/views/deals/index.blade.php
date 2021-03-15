@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">Deals</h3> <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.deals.create')) }}"><span class="fa fa-plus"></span>  Add Deal</a></span></div>
        <div class="card-body p-0 table-responsive">
            <table class="table mb-0 card-table table-hover">
                <thead>
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col">Labels</th>
                    <th scope="col">Value</th>
                    <th scope="col">Organisation</th>
                    <th scope="col">Contact person</th>
                    <th scope="col">Expected close</th>
                    <th scope="col">Assigned To</th>
                    <th scope="col" width="240"></th>
                </tr>
                </thead>
                <tbody>
                @foreach($deals as $deal)
                    <tr class="has-link @if($deal->closed_status == 'won') table-success @elseif($deal->closed_status == 'lost') table-danger @endif" data-url="{{ url(route('laravel-crm.deals.show',$deal)) }}">
                        <td>{{ $deal->title }}</td>
                        <td>@include('laravel-crm::partials.labels',[
                            'labels' => $deal->labels,
                            'limit' => 3
                        ])</td>
                        <td>{{ money($deal->amount, $deal->currency) }}</td>
                        <td>{{ $deal->organisation->name ?? null }}</td>
                        <td>{{ $deal->person->name ?? null }}</td>
                        <td>{{ ($deal->expected_close) ? $deal->expected_close->toFormattedDateString() : null }}</td>
                        <td>{{ $deal->assignedToUser->name ?? null }}</td>
                        <td class="disable-link text-right">
                            @if(!$deal->closed_at)
                            <a href="{{  route('laravel-crm.deals.won',$deal) }}" class="btn btn-success btn-sm">Won</a>
                            <a href="{{  route('laravel-crm.deals.lost',$deal) }}" class="btn btn-danger btn-sm">Lost</a>
                            @else
                                <a href="{{  route('laravel-crm.deals.reopen',$deal) }}" class="btn btn-outline-secondary btn-sm">Reopen</a>
                            @endif    
                            <a href="{{  route('laravel-crm.deals.show',$deal) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                            <a href="{{  route('laravel-crm.deals.edit',$deal) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                            <form action="{{ route('laravel-crm.deals.destroy',$deal) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
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
        @if($deals instanceof \Illuminate\Pagination\LengthAwarePaginator )
            <div class="card-footer">
                {{ $deals->links() }}
            </div>
        @endif
    </div>
    </div>

@endsection