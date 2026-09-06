@extends('laravel-crm::pdfs.bold._layout')

@section('boldContent')
    <table class="bold-header-band">
        <tr>
            <td width="60%">
                <h1 class="bold-doc-title">{{ strtoupper(__('laravel-crm::lang.order')) }}</h1>
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
        @if($order->order_id)
            <tr>
                <td class="bold-meta-label">{{ ucfirst(__('laravel-crm::lang.number')) }}</td>
                <td class="bold-meta-value">{{ $order->order_id }}</td>
            </tr>
        @endif
        @if($order->reference)
            <tr>
                <td class="bold-meta-label">{{ ucfirst(__('laravel-crm::lang.reference')) }}</td>
                <td class="bold-meta-value">{{ $order->reference }}</td>
            </tr>
        @endif
    </table>

    <table class="bold-parties bold-block">
        <tr>
            <td>
                <div class="bold-party-heading">{{ ucfirst(__('laravel-crm::lang.to')) }}</div>
                <div class="bold-party-body">
                    {{ $order->organization->name ?? $order->organization->person->name ?? null }}<br />
                    @isset($order->person)
                        {{ $order->person->name }}<br />
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
                <div class="bold-party-heading">{{ ucfirst(__('laravel-crm::lang.from')) }}</div>
                <div class="bold-party-body">
                    @if($contactDetails ?? null)
                        {!! nl2br($contactDetails) !!}
                    @else
                        {{ $fromName }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    @if($order->description)
        <div class="bold-note bold-block">
            <div class="bold-note-heading">{{ ucfirst(__('laravel-crm::lang.description')) }}</div>
            <div class="bold-note-body">{!! nl2br($order->description) !!}</div>
        </div>
    @endif

    <table class="bold-items">
        <thead>
            <tr>
                <th>{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                <th class="bold-num">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                <th class="bold-num">{{ ucfirst(__('laravel-crm::lang.qty')) }}</th>
                <th class="bold-num">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
                <th>{{ ucfirst(__('laravel-crm::lang.comments')) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderProducts->whereNotNull('product_id')->sortBy([['order', 'asc'], ['created_at', 'asc']]) as $orderProduct)
                <tr>
                    <td>{{ $orderProduct->product?->name }}</td>
                    <td class="bold-num">{{ money($orderProduct->price ?? null, $orderProduct->currency) }}</td>
                    <td class="bold-num">{{ $orderProduct->quantity }}</td>
                    <td class="bold-num">{{ money($orderProduct->amount ?? null, $orderProduct->currency) }}</td>
                    <td>{{ $orderProduct->comments }}</td>
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
                            <td class="bold-totals-value">{{ money($order->subtotal, $order->currency) }}</td>
                        </tr>
                        @if($order->discount > 0)
                            <tr>
                                <td class="bold-totals-label">{{ ucfirst(__('laravel-crm::lang.discount')) }}</td>
                                <td class="bold-totals-value">{{ money($order->discount, $order->currency) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="bold-totals-label">{{ ucfirst(__('laravel-crm::lang.tax')) }}</td>
                            <td class="bold-totals-value">{{ money($order->tax, $order->currency) }}</td>
                        </tr>
                        <tr class="bold-totals-final">
                            <td class="bold-totals-label">{{ ucfirst(__('laravel-crm::lang.total')) }}</td>
                            <td class="bold-totals-value">{{ money($order->total, $order->currency) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if($order->terms)
        <div class="bold-note bold-block">
            <div class="bold-note-heading">{{ ucfirst(__('laravel-crm::lang.terms')) }}</div>
            <div class="bold-note-body">{!! nl2br($order->terms) !!}</div>
        </div>
    @endif
@endsection
