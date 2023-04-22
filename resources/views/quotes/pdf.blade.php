@extends('laravel-crm::layouts.document')

@section('content')

    <table class="table table-sm table-items">
        <tbody>
            <tr>
                <td width="50%"> 
                    <h1>{{ strtoupper(__('laravel-crm::lang.quote')) }}</h1>
                    @if($quote->reference)
                        <p><strong>{{ ucfirst(__('laravel-crm::lang.reference')) }}</strong> {{ $quote->reference }}</p>
                    @endif
                    @if($quote->issue_at)
                        <p><strong>{{ ucfirst(__('laravel-crm::lang.issue_date')) }}</strong> {{ $quote->issue_at->format($dateFormat) }}</p>
                    @endif
                    @if($quote->expire_at)
                        <p><strong>{{ ucfirst(__('laravel-crm::lang.expiry_date')) }}</strong>  {{ $quote->expire_at->format($dateFormat) }}</p>
                    @endif
                </td>
                <td width="50%" style="text-align: right">
                    @if($logo)
                        <img src="{{ asset('storage/'.$logo) }}" height="140" />
                    @endif
                </td>
            </tr>
            <tr>
                <td>
                    <strong>{{ ucfirst(__('laravel-crm::lang.issued_to')) }}</strong><br />
                    {{ $quote->organisation->name ?? $quote->organisation->person->name }}<br />
                    @isset($quote->person)
                    {{ $quote->person->name }}<br />
                    @endisset
                    @if(isset($organisation_address))
                        @if($organisation_address->line1)
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
                </td>
                
                <td>
                    <strong>{{ ucfirst(__('laravel-crm::lang.from')) }}</strong><br />
                    {{ $fromName }}<br />
                    {{-- 19-21 South Steyne<br />
                     MANLY NSW 2095<br />
                     Australia--}}
                </td>
            </tr>
        </tbody>
    </table>
    @if($quote->description)
        <table class="table table-bordered table-sm table-items">
          <tbody>
            <tr>
                <td><h4>{{ ucfirst(__('laravel-crm::lang.description')) }}</h4>
                    {!! nl2br($quote->description) !!}</td>
            </tr>
          </tbody>  
        </table>
    @endif
    <table class="table table-bordered table-sm table-items">
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
    @if($quote->terms)
        <table class="table table-bordered table-sm table-items">
            <tbody>
            <tr>
                <td>
                    <h4>{{ ucfirst(__('laravel-crm::lang.terms')) }}</h4>
                    {!! nl2br($quote->terms) !!}
                </td>
            </tr>
            </tbody>
        </table>
    @endif
@endsection
