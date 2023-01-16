@extends('laravel-crm::layouts.portal')

@section('content')

    <header>
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm fixed-top">
            <div class="container-fluid">
                <h1 class="navbar-brand mb-0" href="#">
                    {{ money($invoice->total, $invoice->currency) }} {{ $invoice->currency }}
                    {{--@if($invoice->accepted_at)
                        <small><span class="badge badge-success">{{ ucfirst(__('laravel-crm::lang.accepted')) }}</span></small>
                    @elseif($invoice->rejected_at)
                        <small><span class="badge badge-danger">{{ ucfirst(__('laravel-crm::lang.rejected')) }}</span></small>
                    @elseif(\Carbon\Carbon::now() <= $invoice->expire_at)
                        <small><span class="badge badge-secondary">{{ ucfirst(__('laravel-crm::lang.expires_in')) }} {{ $invoice->expire_at->diffForHumans() }}</span></small>
                    @else
                        <small><span class="badge badge-danger">{{ ucfirst(__('laravel-crm::lang.invoice_expired')) }}</span></small>
                    @endif--}}
                </h1>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
                       {{-- @if($invoice->accepted_at)
                            <div class="alert alert-success m-0" role="alert">
                                {{ ucfirst(__('laravel-crm::lang.you_have_accepted_this_invoice')) }}.
                            </div>
                        @elseif($invoice->rejected_at)
                            <div class="alert alert-danger m-0" role="alert">
                                {{ ucfirst(__('laravel-crm::lang.you_have_rejected_this_invoice')) }}.
                            </div>
                        @elseif(\Carbon\Carbon::now() <= $invoice->expire_at)
                            <li class="nav-item mr-2">
                                <form action="{{ url()->current() }}?signature={{ request()->input('signature') }}" method="POST" class="form-check-inline mr-0">
                                    {{ csrf_field() }}
                                    <x-form-input name="action" value="accept" type="hidden" />
                                    <button class="btn btn-outline-success" type="submit">{{ ucfirst(__('laravel-crm::lang.accept')) }}</button>
                                </form>
                            </li>
                            <li class="nav-item mr-2">
                                <form action="{{ url()->current() }}?signature={{ request()->input('signature') }}" method="POST" class="form-check-inline mr-0">
                                    {{ csrf_field() }}
                                    <x-form-input name="action" value="reject" type="hidden" />
                                    <button class="btn btn-outline-danger" type="submit">{{ ucfirst(__('laravel-crm::lang.reject')) }}</button>
                                </form>
                            </li>
                        @endif--}}
                        {{--<li class="nav-item">
                            <a class="btn btn-outline-secondary" href="#">Download</a>
                        </li>--}}
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main role="main" class="flex-shrink-0">
        <div class="container">
            <div class="row card shadow-sm mb-4">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col px-5 py-4">
                            <h1 class="card-title pricing-card-title py-4 m-0">{{ ucfirst(__('laravel-crm::lang.invoice')) }}</h1>
                        </div>
                        <div class="col px-5 py-4 text-right">
                            @if($logo)
                                <img src="{{ asset('storage/'.$logo) }}" height="80" />
                            @endif    
                        </div>
                    </div>
                    <hr class="m-0" />
                    <div class="row">
                        <div class="col px-5 py-4">
                            <div class="row py-1">
                                <div class="col-3">
                                    <strong>{{ ucfirst(__('laravel-crm::lang.to')) }}</strong>
                                </div>
                                <div class="col">
                                    {{ $invoice->organisation->name ?? $invoice->organisation->person->name }}<br />
                                    @if(isset($organisation_address))
                                        @if($organisation_address->line2)
                                        {{ $organisation_address->line1 }}<br />
                                        @endif
                                        @if($organisation_address->line2)
                                            {{ $organisation_address->line2 }}<br />
                                        @endif
                                        @if($organisation_address->line3)
                                            {{ $organisation_address->line3 }}<br />
                                        @endif
                                        @if($organisation_address->city || $organisation_address->state || $organisation_address->postcode)
                                        {{ $organisation_address->city }} {{ $organisation_address->state }} {{ $organisation_address->postcode }}<br />
                                        @endif
                                        {{ $organisation_address->country }}
                                    @elseif($address)
                                        {{ $address->line1 }}<br />
                                        @if($address->line2)
                                            {{ $address->line2 }}<br />
                                        @endif
                                        @if($address->line3)
                                            {{ $address->line3 }}<br />
                                        @endif
                                        {{ $address->city }}<br />
                                        {{ $address->country }}
                                    @endif
                                </div>
                            </div>
                            @if($invoice->reference)
                            <div class="row py-1">
                                <div class="col-3">
                                    <strong>{{ ucfirst(__('laravel-crm::lang.reference')) }}</strong>
                                </div>
                                <div class="col">
                                    {{ $invoice->reference }}
                                </div>
                            </div>
                            @endif
                            @if($invoice->issue_date)
                            <div class="row py-1">
                                <div class="col-3">
                                    <strong>{{ ucfirst(__('laravel-crm::lang.issue_date')) }}</strong>
                                </div>
                                <div class="col">
                                    {{ $invoice->issue_date->toFormattedDateString() }}
                                </div>
                            </div>
                            @endif
                            @if($invoice->due_date)
                            <div class="row py-1">
                                <div class="col-3">
                                    <strong>{{ ucfirst(__('laravel-crm::lang.due_date')) }}</strong>
                                </div>
                                <div class="col">
                                    {{ $invoice->due_date->toFormattedDateString() }}
                                </div>
                            </div>
                            @endif    
                        </div>
                        <div class="col px-5 py-4">
                            <div class="row py-1">
                                <div class="col-3">
                                    <strong>{{ ucfirst(__('laravel-crm::lang.from')) }}</strong>
                                </div>
                                <div class="col">
                                    {{ $fromName }}<br />
                                   {{-- 19-21 South Steyne<br />
                                    MANLY NSW 2095<br />
                                    Australia--}}
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr />
                    <div class="row py-1">
                        <div class="col px-5 py-1">
                            <table class="table table-hover mb-0">
                                <thead>
                                <tr>
                                    <th scope="col">{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                                    <th scope="col">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                                    <th scope="col">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
                                    <th scope="col">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($invoice->invoiceLines()->whereNotNull('product_id')->get() as $invoiceLine)
                                    <tr>
                                        <td>{{ $invoiceLine->product->name }}</td>
                                        <td>{{ money($invoiceLine->price ?? null, $invoiceLine->currency) }}</td>
                                        <td>{{ $invoiceLine->quantity }}</td>
                                        <td>{{ money($invoiceLine->amount ?? null, $invoiceLine->currency) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td><strong>{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</strong></td>
                                    <td>{{ money($invoice->subtotal, $invoice->currency) }}</td>
                                </tr>
                                @if($invoice->discount > 0)
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td><strong>{{ ucfirst(__('laravel-crm::lang.discount')) }}</strong></td>
                                    <td>{{ money($invoice->discount, $invoice->currency) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td><strong>{{ ucfirst(__('laravel-crm::lang.tax')) }}</strong></td>
                                    <td>{{ money($invoice->tax, $invoice->currency) }}</td>
                                </tr>
                                {{--<tr>
                                    <td></td>
                                    <td></td>
                                    <td><strong>{{ ucfirst(__('laravel-crm::lang.adjustment')) }}</strong></td>
                                    <td>{{ money($invoice->adjustments, $invoice->currency) }}</td>
                                </tr>--}}
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td><strong>{{ ucfirst(__('laravel-crm::lang.total')) }}</strong></td>
                                    <td>{{ money($invoice->total, $invoice->currency) }}</td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <hr class="m-0" />
                    <div class="row py-1">
                        <div class="col px-5 py-4">
                            <h5>{{ ucfirst(__('laravel-crm::lang.terms')) }}</h5>
                            {!! nl2br($invoice->terms) !!}
                        </div>
                    </div>
                </div>  
            </div>
        </div>
    </main>

@endsection