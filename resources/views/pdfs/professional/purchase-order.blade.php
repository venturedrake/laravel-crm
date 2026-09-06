@extends('laravel-crm::pdfs.professional._layout')

@section('professionalContent')
    <table class="prof-header prof-block">
        <tr>
            <td width="45%">
                <h1 class="prof-doc-title">{{ ucfirst(__('laravel-crm::lang.purchase_order')) }}</h1>
            </td>
            <td width="55%">
                @if($logo)
                    <img class="prof-brand-logo" src="{{ str_starts_with($logo, 'data:') ? $logo : asset('storage/'.$logo) }}" alt="" />
                @endif
                @if(! $logo && $fromName)
                    <div class="prof-brand-name">{{ $fromName }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="prof-meta prof-block">
        <tr>
            <td class="prof-meta-label">{{ ucfirst(__('laravel-crm::lang.purchase_order_number')) }}</td>
            <td class="prof-meta-value">{{ $purchaseOrder->xeroPurchaseOrder->number ?? $purchaseOrder->purchase_order_id }}</td>
        </tr>
        <tr>
            <td class="prof-meta-label">{{ ucfirst(__('laravel-crm::lang.purchase_order_date')) }}</td>
            <td class="prof-meta-value">{{ $purchaseOrder->issue_date?->format($dateFormat) }}</td>
        </tr>
        @if($purchaseOrder->delivery_date)
            <tr>
                <td class="prof-meta-label">{{ ucfirst(__('laravel-crm::lang.delivery_date')) }}</td>
                <td class="prof-meta-value">{{ $purchaseOrder->delivery_date->format($dateFormat) }}</td>
            </tr>
        @endif
        @if($purchaseOrder->reference || ($purchaseOrder->xeroPurchaseOrder && $purchaseOrder->xeroPurchaseOrder->reference))
            <tr>
                <td class="prof-meta-label">{{ ucfirst(__('laravel-crm::lang.reference')) }}</td>
                <td class="prof-meta-value">{{ $purchaseOrder->xeroPurchaseOrder->reference ?? $purchaseOrder->reference }}</td>
            </tr>
        @endif
    </table>

    <table class="prof-parties">
        <tr>
            <td>
                <div class="prof-party-heading">{{ ucfirst(__('laravel-crm::lang.supplier')) }}</div>
                <div class="prof-party-body">
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
                <div class="prof-party-heading">{{ ucfirst(__('laravel-crm::lang.delivery_details')) }}</div>
                <div class="prof-party-body">
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

    <table class="prof-items">
        <thead>
            <tr>
                <th>{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                <th class="prof-num">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                <th class="prof-num">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
                <th class="prof-num">{{ $taxName }}</th>
                <th class="prof-num">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
                <th>{{ ucfirst(__('laravel-crm::lang.comments')) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->purchaseOrderLines->whereNotNull('product_id') as $purchaseOrderLine)
                <tr>
                    <td>{{ $purchaseOrderLine->product->name ?? null }}</td>
                    <td class="prof-num">{{ money($purchaseOrderLine->price ?? null, $purchaseOrderLine->currency) }}</td>
                    <td class="prof-num">{{ $purchaseOrderLine->quantity }}</td>
                    <td class="prof-num">{{ money($purchaseOrderLine->tax_amount ?? null, $purchaseOrderLine->currency) }}</td>
                    <td class="prof-num">{{ money($purchaseOrderLine->amount ?? null, $purchaseOrderLine->currency) }}</td>
                    <td>{{ $purchaseOrderLine->comments }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="prof-totals-wrap">
        <table>
            <tr>
                <td class="prof-totals-spacer"></td>
                <td class="prof-totals">
                    <table>
                        <tr>
                            <td class="prof-totals-label">{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</td>
                            <td class="prof-totals-value">{{ money($purchaseOrder->subtotal, $purchaseOrder->currency) }}</td>
                        </tr>
                        @if($purchaseOrder->discount > 0)
                            <tr>
                                <td class="prof-totals-label">{{ ucfirst(__('laravel-crm::lang.discount')) }}</td>
                                <td class="prof-totals-value">{{ money($purchaseOrder->discount, $purchaseOrder->currency) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="prof-totals-label">{{ $taxName }}</td>
                            <td class="prof-totals-value">{{ money($purchaseOrder->tax, $purchaseOrder->currency) }}</td>
                        </tr>
                        <tr class="prof-totals-final">
                            <td class="prof-totals-label">{{ ucfirst(__('laravel-crm::lang.total')) }}</td>
                            <td class="prof-totals-value">{{ money($purchaseOrder->total, $purchaseOrder->currency) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if($purchaseOrder->delivery_instructions)
        <div class="prof-note prof-block">
            <div class="prof-note-heading">{{ ucfirst(__('laravel-crm::lang.delivery_instructions')) }}</div>
            <div class="prof-note-body">{!! nl2br($purchaseOrder->delivery_instructions) !!}</div>
        </div>
    @endif

    @if($purchaseOrder->terms)
        <div class="prof-note prof-block">
            <div class="prof-note-heading">{{ ucfirst(__('laravel-crm::lang.terms')) }}</div>
            <div class="prof-note-body">{!! nl2br($purchaseOrder->terms) !!}</div>
        </div>
    @endif
@endsection
