@extends('laravel-crm::layouts.document')

@section('content')

    <table class="table table-sm table-items">
        <tbody>
            <tr>
                <td width="50%"> 
                    <h1>{{ strtoupper(__('laravel-crm::lang.order')) }}</h1>
                    @if($order->reference)
                        <p><strong>{{ ucfirst(__('laravel-crm::lang.reference')) }}</strong> {{ $order->reference }}</p>
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
            <th scope="col">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
            <th scope="col">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
            <th scope="col">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
            <th scope="col">{{ ucfirst(__('laravel-crm::lang.comments')) }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($order->orderProducts()->whereNotNull('product_id')->get() as $orderProduct)
            <tr>
                <td>{{ $orderProduct->product->name }}</td>
                <td>{{ money($orderProduct->price ?? null, $orderProduct->currency) }}</td>
                <td>{{ $orderProduct->quantity }}</td>
                <td>{{ money($orderProduct->amount ?? null, $orderProduct->currency) }}</td>
                <td>{{ $orderProduct->comments }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td></td>
            <td></td>
            <td><strong>{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</strong></td>
            <td>{{ money($order->subtotal, $order->currency) }}</td>
            <td></td>
        </tr>
        @if($order->discount > 0)
            <tr>
                <td></td>
                <td></td>
                <td><strong>{{ ucfirst(__('laravel-crm::lang.discount')) }}</strong></td>
                <td>{{ money($order->discount, $order->currency) }}</td>
                <td></td>
            </tr>
        @endif
        <tr>
            <td></td>
            <td></td>
            <td><strong>{{ ucfirst(__('laravel-crm::lang.tax')) }}</strong></td>
            <td>{{ money($order->tax, $order->currency) }}</td>
            <td></td>
        </tr>
        {{--<tr>
            <td></td>
            <td></td>
            <td><strong>{{ ucfirst(__('laravel-crm::lang.adjustment')) }}</strong></td>
            <td>{{ money($order->adjustments, $order->currency) }}</td>
            <td></td>
        </tr>--}}
        <tr>
            <td></td>
            <td></td>
            <td><strong>{{ ucfirst(__('laravel-crm::lang.total')) }}</strong></td>
            <td>{{ money($order->total, $order->currency) }}</td>
            <td></td>
        </tr>
        </tfoot>
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
@endsection
