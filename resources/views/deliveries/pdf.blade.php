@extends('laravel-crm::layouts.document')

@section('content')

    <table class="table table-sm table-items">
        <tbody>
            <tr>
                <p width="50%"> 
                    <h1>{{ strtoupper(__('laravel-crm::lang.delivery')) }}</h1>
                    @if($order->reference || $delivery->delivery_expected)
                    <p>
                    @endif    
                    @if($order->reference)
                        <strong>{{ ucfirst(__('laravel-crm::lang.reference')) }}</strong> {{ $order->reference }}
                    @endif
                    @if($delivery->delivery_expected)
                        @if($order->reference)
                            <br />
                        @endif    
                        <strong>{{ ucwords(__('laravel-crm::lang.delivery_date')) }}</strong> {{ \Carbon\Carbon::parse($delivery->delivery_expected)->toFormattedDateString() }}
                    @endif
                    @if($order->reference || $delivery->delivery_expected)
                    </p>
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
                    <strong>{{ ucfirst(__('laravel-crm::lang.to')) }}</strong><br />
                    {{ $order->organisation->name ?? $order->organisation->person->name }}<br />
                    @isset($order->person)
                        {{ $order->person->name }}<br />
                    @endisset
                    @if($address->contact)
                        <strong>{{ ucwords(__('laravel-crm::lang.contact')) }}: {{ $address->contact }}</strong><br >
                    @endif
                    @if($address->phone)
                        <strong>{{ ucwords(__('laravel-crm::lang.phone')) }}: {{ $address->phone }}</strong><br >
                    @endif
                    @if($address)
                        {{ $address->line1 }}<br />
                        @if($address->line2)
                            {{ $address->line2 }}<br />
                        @endif
                        @if($address->line3)
                            {{ $address->line3 }}<br />
                        @endif
                        {{ $address->city }}<br />
                        {{ $address->country }}
                    @elseif(isset($organisation_address))
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
    @if($order->description)
        <table class="table table-bordered table-sm table-items">
          <tbody>
            <tr>
                <td><h4>{{ ucfirst(__('laravel-crm::lang.description')) }}</h4>
                    {!! nl2br($order->description) !!}</td>
            </tr>
          </tbody>  
        </table>
    @endif
    <table class="table table-bordered table-sm table-items">
        <thead>
        <tr>
            <th scope="col">{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
            <th scope="col">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
            <th scope="col">{{ ucfirst(__('laravel-crm::lang.comments')) }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($order->orderProducts()->whereNotNull('product_id')->get() as $orderProduct)
            <tr>
                <td>{{ $orderProduct->product->name }}</td>
                <td>{{ $orderProduct->quantity }}</td>
                <td>{{ $orderProduct->comments }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @if($order->terms)
        <table class="table table-bordered table-sm table-items">
            <tbody>
            <tr>
                <td>
                    <h4>{{ ucfirst(__('laravel-crm::lang.terms')) }}</h4>
                    {!! nl2br($order->terms) !!}
                </td>
            </tr>
            </tbody>
        </table>
    @endif

    <table class="table table-bordered table-sm table-delivery">
        <tbody>
        <tr>
            <th width="150">{{ ucfirst(__('laravel-crm::lang.received_by')) }} </th>
            <td></td>
        </tr>
        <tr>
            <th>{{ ucfirst(__('laravel-crm::lang.received_date')) }} </th>
            <td></td>
        </tr>
        <tr>
            <th>{{ ucfirst(__('laravel-crm::lang.signature')) }} </th>
            <td></td>
        </tr>
        </tbody>
    </table>
@endsection
