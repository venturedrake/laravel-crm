@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">{{ $deal->title }}</h3>
            <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.deals.index')) }}"><span class="fa fa-angle-double-left"></span> Back to deals</a> | 
                @if(!$deal->closed_at)
                    <a href="{{  route('laravel-crm.deals.won',$deal) }}" class="btn btn-success btn-sm">Won</a>
                    <a href="{{  route('laravel-crm.deals.lost',$deal) }}" class="btn btn-danger btn-sm">Lost</a>
                @else
                    <a href="{{  route('laravel-crm.deals.reopen',$deal) }}" class="btn btn-outline-secondary btn-sm">Reopen</a>
                @endif
                @include('laravel-crm::partials.navs.activities') |
                <a href="{{ url(route('laravel-crm.deals.edit', $deal)) }}" type="button" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                <form action="{{ route('laravel-crm.deals.destroy',$deal) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="deal"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
</span>
        </div>
        <div class="card-body card-show card-fa-w30">
            <div class="row">
                <div class="col-sm-6 border-right">
                    <h6 class="text-uppercase">Details</h6>
                    <hr />
                    <p><span class="fa fa-tag" aria-hidden="true"></span>@include('laravel-crm::partials.labels',[
                            'labels' => $deal->labels
                    ])</p>
                    <p><span class="fa fa-dollar" aria-hidden="true"></span> {{ money($deal->amount, $deal->currency) }}</p>
                    <p><span class="fa fa-info" aria-hidden="true"></span> {{ $deal->description }}</p>
                    <p><span class="fa fa-user-circle" aria-hidden="true"></span> {{ $deal->assignedToUser->name }}</p>
                    <h6 class="mt-4 text-uppercase"> Contact Person</h6>
                    <hr />
                    <p><span class="fa fa-user" aria-hidden="true"></span> {{ $deal->person->name ?? null }} </p>
                    @isset($email)
                        <p><span class="fa fa-envelope" aria-hidden="true"></span> <a href="mailto:{{ $email->address }}">{{ $email->address }}</a> ({{ ucfirst($email->type) }})</p>
                    @endisset
                    @isset($phone)
                        <p><span class="fa fa-phone" aria-hidden="true"></span> <a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> ({{ ucfirst($phone->type) }})</p>
                    @endisset
                    <h6 class="mt-4 text-uppercase"> Organisation</h6>
                    <hr />
                    <p><span class="fa fa-building" aria-hidden="true"></span> {{ $deal->organisation->name ?? null }}</p>
                    <p><span class="fa fa-map-marker" aria-hidden="true"></span> {{ ($organisation_address) ? \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($organisation_address) : null }} </p>

                    {{--<h6 class="text-uppercase mt-4 section-h6-title"><span>Products (0)</span><span class="float-right"><a href="#" class="btn btn-outline-secondary btn-sm"><span class="fa fa-plus" aria-hidden="true"></span></a></span></h6>
                    <hr />
                    ...--}}
                </div>
                <div class="col-sm-6">
                    <h6 class="text-uppercase">Notes</h6>
                    <hr />
                    ...
                    <h6 class="text-uppercase mt-4">Files</h6>
                    <hr />
                    ...
                    <h6 class="text-uppercase mt-4">Activities</h6>
                    <hr />
                    ...
                </div>
            </div>
        </div>
    </div>

@endsection