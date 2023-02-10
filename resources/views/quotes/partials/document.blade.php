@extends('laravel-crm::layouts.document')

@section('content')

<div class="row">
    <div class="col px-5 py-4">
        <h1 class="card-title pricing-card-title py-4 m-0">{{ ucfirst(__('laravel-crm::lang.quote')) }}</h1>
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
                {{ $quote->organisation->name ?? $quote->organisation->person->name }}<br />
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
        @if($quote->reference)
            <div class="row py-1">
                <div class="col-3">
                    <strong>{{ ucfirst(__('laravel-crm::lang.reference')) }}</strong>
                </div>
                <div class="col">
                    {{ $quote->reference }}
                </div>
            </div>
        @endif
        @if($quote->issue_at)
            <div class="row py-1">
                <div class="col-3">
                    <strong>{{ ucfirst(__('laravel-crm::lang.issue_date')) }}</strong>
                </div>
                <div class="col">
                    {{ $quote->issue_at->toFormattedDateString() }}
                </div>
            </div>
        @endif
        @if($quote->expire_at)
            <div class="row py-1">
                <div class="col-3">
                    <strong>{{ ucfirst(__('laravel-crm::lang.expiry_date')) }}</strong>
                </div>
                <div class="col">
                    {{ $quote->expire_at->toFormattedDateString() }}
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
<hr class="m-0" />
<div class="row py-1">
    <div class="col px-5 py-4">
        {!! nl2br($quote->description) !!}
    </div>
</div>
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
            @foreach($quote->quoteProducts()->whereNotNull('product_id')->get() as $quoteProduct)
                <tr>
                    <td>{{ $quoteProduct->product->name }}</td>
                    <td>{{ money($quoteProduct->price ?? null, $quoteProduct->currency) }}</td>
                    <td>{{ $quoteProduct->quantity }}</td>
                    <td>{{ money($quoteProduct->amount ?? null, $quoteProduct->currency) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td><strong>{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</strong></td>
                <td>{{ money($quote->subtotal, $quote->currency) }}</td>
            </tr>
            @if($quote->discount > 0)
                <tr>
                    <td></td>
                    <td></td>
                    <td><strong>{{ ucfirst(__('laravel-crm::lang.discount')) }}</strong></td>
                    <td>{{ money($quote->discount, $quote->currency) }}</td>
                </tr>
            @endif
            <tr>
                <td></td>
                <td></td>
                <td><strong>{{ ucfirst(__('laravel-crm::lang.tax')) }}</strong></td>
                <td>{{ money($quote->tax, $quote->currency) }}</td>
            </tr>
            {{--<tr>
                <td></td>
                <td></td>
                <td><strong>{{ ucfirst(__('laravel-crm::lang.adjustment')) }}</strong></td>
                <td>{{ money($quote->adjustments, $quote->currency) }}</td>
            </tr>--}}
            <tr>
                <td></td>
                <td></td>
                <td><strong>{{ ucfirst(__('laravel-crm::lang.total')) }}</strong></td>
                <td>{{ money($quote->total, $quote->currency) }}</td>
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
    
@endsection