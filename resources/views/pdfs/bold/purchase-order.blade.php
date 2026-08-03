@extends('laravel-crm::pdfs.bold._layout')

@section('boldContent')
    <table class="bold-header-band">
        <tr>
            <td width="60%">
                <h1 class="bold-doc-title">{{ strtoupper(__('laravel-crm::lang.purchase_order')) }}</h1>
            </td>
            <td width="40%" class="bold-band-brand">
                @if($logo)
                    <span class="bold-band-logo-plate"><img src="{{ str_starts_with($logo, 'data:') ? $logo : asset('storage/'.$logo) }}" alt="" /></span>
                @elseif($fromName)
                    <div class="bold-band-brand-name">{{ $fromName }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="bold-meta bold-block">
        <tr>
            <td class="bold-meta-label">{{ ucfirst(__('laravel-crm::lang.purchase_order_number')) }}</td>
            <td class="bold-meta-value">{{ $purchaseOrder->xeroPurchaseOrder->number ?? $purchaseOrder->purchase_order_id }}</td>
        </tr>
        <tr>
            <td class="bold-meta-label">{{ ucfirst(__('laravel-crm::lang.purchase_order_date')) }}</td>
            <td class="bold-meta-value">{{ $purchaseOrder->issue_date->format($dateFormat) }}</td>
        </tr>
        @if($purchaseOrder->delivery_date)
            <tr>
                <td class="bold-meta-label">{{ ucfirst(__('laravel-crm::lang.delivery_date')) }}</td>
                <td class="bold-meta-value">{{ $purchaseOrder->delivery_date->format($dateFormat) }}</td>
            </tr>
        @endif
        @if($purchaseOrder->reference || ($purchaseOrder->xeroPurchaseOrder && $purchaseOrder->xeroPurchaseOrder->reference))
            <tr>
                <td class="bold-meta-label">{{ ucfirst(__('laravel-crm::lang.reference')) }}</td>
                <td class="bold-meta-value">{{ $purchaseOrder->xeroPurchaseOrder->reference ?? $purchaseOrder->reference }}</td>
            </tr>
        @endif
    </table>

    <table class="bold-parties bold-block">
        <tr>
            <td>
                <div class="bold-party-heading">{{ ucfirst(__('laravel-crm::lang.supplier')) }}</div>
                <div class="bold-party-body">
                    @if($purchaseOrder->organization)
                        {{ $purchaseOrder->organization->name }}<br />
                    @endif
                    @isset($purchaseOrder->person)
                        {{ $purchaseOrder->person->name }}<br />
                    @endisset
                    @if(isset($organization_address))
                        @if($organization_address->line1){{ $organization_address->line1 }}<br />@endif
                        @if($organization_address->line2){{ $organization_address->line2 }}<br />@endif
                        @if($organization_address->line3){{ $organization_address->line3 }}<br />@endif
                        @if($organization_address->city || $organization_address->state || $organization_address->postcode)
                            {{ $organization_address->city }} {{ $organization_address->state }} {{ $organization_address->postcode }}<br />
                        @endif
                        {{ $organization_address->country }}
                    @elseif($address)
                        {{ $address->line1 }}<br />
                        @if($address->line2){{ $address->line2 }}<br />@endif
                        @if($address->line3){{ $address->line3 }}<br />@endif
                        {{ $address->city }}<br />
                        {{ $address->country }}
                    @endif
                </div>
            </td>
            <td>
                <div class="bold-party-heading">{{ ucfirst(__('laravel-crm::lang.delivery_details')) }}</div>
                <div class="bold-party-body">
                    {{ $fromName }}<br />
                    @if($purchaseOrder->delivery_type == 'pickup')
                        {{ strtoupper(__('laravel-crm::lang.pickup')) }}
                    @elseif($purchaseOrder->address)
                        {{ $purchaseOrder->address->line1 }}<br />
                        @if($purchaseOrder->address->line2){{ $purchaseOrder->address->line2 }}<br />@endif
                        @if($purchaseOrder->address->line3){{ $purchaseOrder->address->line3 }}<br />@endif
                        {{ $purchaseOrder->address->city }} {{ $purchaseOrder->address->state }} {{ $purchaseOrder->address->postcode }}<br />
                        {{ $purchaseOrder->address->country }}
                        @if($purchaseOrder->address->contact)
                            <br /><strong>{{ ucfirst(__('laravel-crm::lang.delivery_contact')) }}:</strong> {{ $purchaseOrder->address->contact }}
                        @endif
                        @if($purchaseOrder->address->phone)
                            <br /><strong>{{ ucfirst(__('laravel-crm::lang.delivery_phone')) }}:</strong> {{ $purchaseOrder->address->phone }}
                        @endif
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="bold-items">
        <thead>
            <tr>
                <th>{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                <th class="bold-num">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                <th class="bold-num">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
                <th class="bold-num">{{ $taxName }}</th>
                <th class="bold-num">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
                <th>{{ ucfirst(__('laravel-crm::lang.comments')) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->purchaseOrderLines->whereNotNull('product_id') as $purchaseOrderLine)
                <tr>
                    <td>{{ $purchaseOrderLine->product->name ?? null }}</td>
                    <td class="bold-num">{{ money($purchaseOrderLine->price ?? null, $purchaseOrderLine->currency) }}</td>
                    <td class="bold-num">{{ $purchaseOrderLine->quantity }}</td>
                    <td class="bold-num">{{ money($purchaseOrderLine->tax_amount ?? null, $purchaseOrderLine->currency) }}</td>
                    <td class="bold-num">{{ money($purchaseOrderLine->amount ?? null, $purchaseOrderLine->currency) }}</td>
                    <td>{{ $purchaseOrderLine->comments }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="bold-totals-wrap">
        <table>
            <tr>
                <td class="bold-totals-spacer"></td>
                <td class="bold-totals">
                    <table>
                        <tr>
                            <td class="bold-totals-label">{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</td>
                            <td class="bold-totals-value">{{ money($purchaseOrder->subtotal, $purchaseOrder->currency) }}</td>
                        </tr>
                        @if($purchaseOrder->discount > 0)
                            <tr>
                                <td class="bold-totals-label">{{ ucfirst(__('laravel-crm::lang.discount')) }}</td>
                                <td class="bold-totals-value">{{ money($purchaseOrder->discount, $purchaseOrder->currency) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="bold-totals-label">{{ $taxName }}</td>
                            <td class="bold-totals-value">{{ money($purchaseOrder->tax, $purchaseOrder->currency) }}</td>
                        </tr>
                        <tr class="bold-totals-final">
                            <td class="bold-totals-label">{{ ucfirst(__('laravel-crm::lang.total')) }}</td>
                            <td class="bold-totals-value">{{ money($purchaseOrder->total, $purchaseOrder->currency) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if($purchaseOrder->delivery_instructions)
        <div class="bold-note bold-block">
            <div class="bold-note-heading">{{ ucfirst(__('laravel-crm::lang.delivery_instructions')) }}</div>
            <div class="bold-note-body">{!! nl2br(e($purchaseOrder->delivery_instructions)) !!}</div>
        </div>
    @endif

    @if($purchaseOrder->terms)
        <div class="bold-note bold-block">
            <div class="bold-note-heading">{{ ucfirst(__('laravel-crm::lang.terms')) }}</div>
            <div class="bold-note-body">{!! nl2br(e($purchaseOrder->terms)) !!}</div>
        </div>
    @endif
@endsection
