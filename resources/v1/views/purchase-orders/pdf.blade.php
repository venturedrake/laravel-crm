@extends('laravel-crm::layouts.document')

@section('content')

    <table class="table table-sm table-items">
        <tbody>
            <tr>
                <td width="50%"> 
                    <h1>{{ strtoupper(__('laravel-crm::lang.purchase_order')) }}</h1>
                    <p>
                    <strong>{{ ucfirst(__('laravel-crm::lang.purchase_order_date')) }}</strong> {{ $purchaseOrder->issue_date->format($dateFormat) }}<br />
                    <strong>{{ ucfirst(__('laravel-crm::lang.purchase_order_number')) }}</strong> {{ $purchaseOrder->xeroPurchaseOrder->number ?? $purchaseOrder->purchase_order_id  }}
                    @if($purchaseOrder->delivery_date)
                    <br /><strong>{{ ucfirst(__('laravel-crm::lang.delivery_date')) }}</strong> {{ $purchaseOrder->delivery_date->format($dateFormat) }}
                    @endif
                    @if($purchaseOrder->reference || ($purchaseOrder->xeroPurchaseOrder && $purchaseOrder->xeroPurchaseOrder->reference))
                    <br /><strong>{{ ucfirst(__('laravel-crm::lang.reference')) }}</strong> {{ $purchaseOrder->xeroPurchaseOrder->reference ?? $purchaseOrder->reference }}<br />
                    @endif
                    </p>
                </td>
                <td width="50%" style="text-align: right">
                    @if($logo)
                        <img src="{{ asset('storage/'.$logo) }}" height="140" />
                    @endif
                </td>
            </tr>
            <tr>
                <td>
                    <strong>{{ ucfirst(__('laravel-crm::lang.supplier')) }}</strong><br />
                    @if($purchaseOrder->organisation)
                        {{ $purchaseOrder->organisation->name }}<br />
                    @endif
                    @isset($purchaseOrder->person)
                        {{ $purchaseOrder->person->name }}<br />
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
                    <strong>{{ ucfirst(__('laravel-crm::lang.delivery_details')) }}</strong><br />
                    {{ $fromName }}<br />
                    @if($purchaseOrder->delivery_type == 'pickup')
                        {{ strtoupper(__('laravel-crm::lang.pickup')) }}
                    @else    
                        @if($purchaseOrder->address)
                            {{ $purchaseOrder->address->line1 }}<br />
                            @if($purchaseOrder->address->line2)
                                {{ $purchaseOrder->address->line2 }}<br />
                            @endif
                            @if($purchaseOrder->address->line3)
                                {{ $purchaseOrder->address->line3 }}<br />
                            @endif
                            {{ $purchaseOrder->address->city }} {{ $purchaseOrder->address->state }} {{ $purchaseOrder->address->postcode }}<br />
                            {{ $purchaseOrder->address->country }}
                            <br /><strong>{{ ucfirst(__('laravel-crm::lang.delivery_contact')) }}</strong><br />
                            {{ $purchaseOrder->address->contact }}
                            <strong>{{ ucfirst(__('laravel-crm::lang.delivery_phone')) }}</strong><br />
                            {{ $purchaseOrder->address->phone }}
                        @endif
                    @endif    
                </td>
            </tr>
        </tbody>
    </table>
    <table class="table table-bordered table-sm table-items">
        <thead>
        <tr>
            <th scope="col">{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
            <th scope="col">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
            <th scope="col">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
            <th scope="col">{{ $taxName }}</th>
            <th scope="col">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
            <th scope="col">{{ ucfirst(__('laravel-crm::lang.comments')) }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($purchaseOrder->purchaseOrderLines()->whereNotNull('product_id')->get() as $purchaseOrderLine)
            <tr>
                <td>{{ $purchaseOrderLine->product->name ?? null}}</td>
                <td>{{ money($purchaseOrderLine->price ?? null, $purchaseOrderLine->currency) }}</td>
                <td>{{ $purchaseOrderLine->quantity }}</td>
                <td>{{ money($purchaseOrderLine->tax_amount ?? null, $purchaseOrderLine->currency) }}</td>
                <td>{{ money($purchaseOrderLine->amount ?? null, $purchaseOrderLine->currency) }}</td>
                <td>{{ $purchaseOrderLine->comments }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td><strong>{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</strong></td>
            <td>{{ money($purchaseOrder->subtotal, $purchaseOrder->currency) }}</td>
            <td></td>
        </tr>
        @if($purchaseOrder->discount > 0)
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td><strong>{{ ucfirst(__('laravel-crm::lang.discount')) }}</strong></td>
                <td>{{ money($purchaseOrder->discount, $purchaseOrder->currency) }}</td>
                <td></td>
            </tr>
        @endif
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td><strong>{{ $taxName }}</strong></td>
            <td>{{ money($purchaseOrder->tax, $purchaseOrder->currency) }}</td>
            <td></td>
        </tr>
        {{--<tr>
            <td></td>
            <td></td>
            <td></td>
            <td><strong>{{ ucfirst(__('laravel-crm::lang.adjustment')) }}</strong></td>
            <td>{{ money($purchaseOrder->adjustments, $purchaseOrder->currency) }}</td>
            <td></td>
        </tr>--}}
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td><strong>{{ ucfirst(__('laravel-crm::lang.total')) }}</strong></td>
            <td>{{ money($purchaseOrder->total, $purchaseOrder->currency) }}</td>
            <td></td>
        </tr>
        </tfoot>
    </table>
    @if($purchaseOrder->delivery_instructions)
        <table class="table table-bordered table-sm table-items">
            <tbody>
            <tr>
                <td>
                    <h4>{{ ucfirst(__('laravel-crm::lang.delivery_instructions')) }}</h4>
                    {!! nl2br($purchaseOrder->delivery_instructions) !!}
                </td>
            </tr>
            </tbody>
        </table>
    @endif
    @if($purchaseOrder->terms)
        <table class="table table-bordered table-sm table-items">
            <tbody>
            <tr>
                <td>
                    <h4>{{ ucfirst(__('laravel-crm::lang.terms')) }}</h4>
                    {!! nl2br($purchaseOrder->terms) !!}
                </td>
            </tr>
            </tbody>
        </table>
    @endif
@endsection
