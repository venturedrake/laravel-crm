@extends('laravel-crm::layouts.portal')

@section('content')

    <header>
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm fixed-top"> 
            <div class="container-fluid">
                <h1 class="navbar-brand mb-0" href="#">
                    {{ money($quote->total, $quote->currency) }} <small>{{ $quote->currency }}</small>
                    @if($quote->accepted_at)
                        <small><span class="badge badge-success">{{ ucfirst(__('laravel-crm::lang.accepted')) }}</span></small>
                    @elseif($quote->rejected_at)
                        <small><span class="badge badge-danger">{{ ucfirst(__('laravel-crm::lang.rejected')) }}</span></small>
                    @elseif(\Carbon\Carbon::now() <= $quote->expire_at)
                        <small><span class="badge badge-secondary">{{ ucfirst(__('laravel-crm::lang.expires_in')) }} {{ $quote->expire_at->diffForHumans() }}</span></small>
                    @else
                        <small><span class="badge badge-danger">{{ ucfirst(__('laravel-crm::lang.quote_expired')) }}</span></small>
                    @endif
                </h1>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
                        @if($quote->accepted_at)
                            <div class="alert alert-success m-0" role="alert">
                                {{ ucfirst(__('laravel-crm::lang.you_have_accepted_this_quote')) }}.
                            </div>
                        @elseif($quote->rejected_at)
                            <div class="alert alert-danger m-0" role="alert">
                                {{ ucfirst(__('laravel-crm::lang.you_have_rejected_this_quote')) }}.
                            </div>
                        @elseif(\Carbon\Carbon::now() <= $quote->expire_at)
                            <li class="nav-item mr-2">
                                <form action="{{ url()->current() }}?signature={{ request()->input('signature') }}&expires={{ request()->input('expires') }}" method="POST" class="form-check-inline mr-0">
                                    {{ csrf_field() }}
                                    <x-form-input name="action" value="accept" type="hidden" />
                                    <button class="btn btn-outline-success" type="submit">{{ ucfirst(__('laravel-crm::lang.accept')) }}</button>
                                </form>
                            </li>
                            <li class="nav-item mr-2">
                                <form action="{{ url()->current() }}?signature={{ request()->input('signature') }}&expires={{ request()->input('expires') }}" method="POST" class="form-check-inline mr-0">
                                    {{ csrf_field() }}
                                    <x-form-input name="action" value="reject" type="hidden" />
                                    <button class="btn btn-outline-danger" type="submit">{{ ucfirst(__('laravel-crm::lang.reject')) }}</button>
                                </form>
                            </li>
                        @endif
                        <li class="nav-item">
                            <form action="{{ url()->current() }}?signature={{ request()->input('signature') }}&expires={{ request()->input('expires') }}" method="POST" class="form-check-inline mr-0">
                                {{ csrf_field() }}
                                <x-form-input name="action" value="download" type="hidden" />
                                <button class="btn btn-outline-secondary" type="submit"><span class="fa fa-download" aria-hidden="true"></span> {{ ucfirst(__('laravel-crm::lang.download')) }}</button>
                            </form>
                        </li>
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
                            <h1 class="card-title pricing-card-title py-3 m-0 text-uppercase">{{ ucfirst(__('laravel-crm::lang.quote')) }}</h1>
                            @if($quote->reference)
                                <p class="mb-0"><strong>{{ ucfirst(__('laravel-crm::lang.reference')) }}</strong> {{ $quote->reference }}</p>
                            @endif
                            @if($quote->issue_at)
                                <p class="mb-0"><strong>{{ ucfirst(__('laravel-crm::lang.issue_date')) }}</strong> {{ $quote->issue_at->toFormattedDateString() }}</p>
                            @endif
                            @if($quote->expire_at)
                                <p class="mb-0"><strong>{{ ucfirst(__('laravel-crm::lang.expiry_date')) }}</strong>  {{ $quote->expire_at->toFormattedDateString() }}</p>
                            @endif
                        </div>
                        <div class="col px-5 py-4 text-right">
                            @if($logo)
                                <img src="{{ asset('storage/'.$logo) }}" height="160" />
                            @endif
                        </div>
                    </div>
                    <hr class="m-0" />
                    <div class="row">
                        <div class="col px-5 py-4">
                            <div class="row py-1">
                                <div class="col-3">
                                    <strong>{{ ucfirst(__('laravel-crm::lang.issued_to')) }}</strong>
                                </div>
                                <div class="col">
                                    {{ $quote->organisation->name ?? $quote->organisation->person->name ?? null }}<br />
                                    {{ $quote->person->name ?? null }}<br />
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
                    <hr class="m-0" />
                    @if($quote->description)
                    <div class="row py-1">
                        <div class="col px-5 py-4">
                            <h5>{{ ucfirst(__('laravel-crm::lang.description')) }}</h5>
                            {!! nl2br($quote->description) !!}
                        </div>
                    </div>
                    @endif
                    <div class="row py-1">
                        <div class="col px-5 py-1">
                            <table class="table table-hover mb-0">
                                <thead>
                                <tr>
                                    <th scope="col">{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                                    <th scope="col">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                                    <th scope="col">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
                                    <th scope="col">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
                                    <th scope="col">{{ ucfirst(__('laravel-crm::lang.comments')) }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($quote->quoteProducts()->whereNotNull('product_id')->get() as $quoteProduct)
                                    <tr>
                                        <td>{{ $quoteProduct->product->name }}</td>
                                        <td>{{ money($quoteProduct->price ?? null, $quoteProduct->currency) }}</td>
                                        <td>{{ $quoteProduct->quantity }}</td>
                                        <td>{{ money($quoteProduct->amount ?? null, $quoteProduct->currency) }}</td>
                                        <td>{{ $quoteProduct->comments }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td><strong>{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</strong></td>
                                    <td>{{ money($quote->subtotal, $quote->currency) }}</td>
                                    <td></td>
                                </tr>
                                @if($quote->discount > 0)
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td><strong>{{ ucfirst(__('laravel-crm::lang.discount')) }}</strong></td>
                                        <td>{{ money($quote->discount, $quote->currency) }}</td>
                                        <td></td>
                                    </tr>
                                @endif
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td><strong>{{ ucfirst(__('laravel-crm::lang.tax')) }}</strong></td>
                                    <td>{{ money($quote->tax, $quote->currency) }}</td>
                                    <td></td>
                                </tr>
                                {{--<tr>
                                    <td></td>
                                    <td></td>
                                    <td><strong>{{ ucfirst(__('laravel-crm::lang.adjustment')) }}</strong></td>
                                    <td>{{ money($quote->adjustments, $quote->currency) }}</td>
                                    <td></td>
                                </tr>--}}
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td><strong>{{ ucfirst(__('laravel-crm::lang.total')) }}</strong></td>
                                    <td>{{ money($quote->total, $quote->currency) }}</td>
                                    <td></td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <hr class="m-0" />
                    <div class="row py-1">
                        <div class="col px-5 py-4">
                            <h5>{{ ucfirst(__('laravel-crm::lang.terms')) }}</h5>
                            {!! nl2br($quote->terms) !!}
                        </div>
                    </div>

                </div>  
            </div>
        </div>
    </main>

@endsection