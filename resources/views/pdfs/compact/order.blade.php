@extends('laravel-crm::pdfs.compact._layout')

@section('compactContent')
    <table class="compact-header">
        <tr>
            <td width="60%">
                @if($logo)
                    <img class="compact-brand-logo" src="{{ str_starts_with($logo, 'data:') ? $logo : asset('storage/'.$logo) }}" alt="" />
                @endif
                @if(! $logo && $fromName)
                    <div class="compact-brand-name">{{ $fromName }}</div>
                @endif
            </td>
            <td width="40%">
                <h1 class="compact-doc-title">{{ __('laravel-crm::lang.order') }}</h1>
            </td>
        </tr>
    </table>

    <hr class="compact-rule" />

    <table class="compact-meta compact-block">
        <tr>
            <td class="compact-meta-label">{{ ucfirst(__('laravel-crm::lang.number')) }}</td>
            <td class="compact-meta-value">{{ $order->order_id }}</td>
            <td class="compact-meta-label">{{ ucfirst(__('laravel-crm::lang.reference')) }}</td>
            <td class="compact-meta-value">{{ $order->reference }}</td>
        </tr>
    </table>

    <table class="compact-parties compact-block">
        <tr>
            <td>
                <div class="compact-party-heading">{{ ucfirst(__('laravel-crm::lang.to')) }}</div>
                <div class="compact-party-body">
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
                <div class="compact-party-heading">{{ ucfirst(__('laravel-crm::lang.from')) }}</div>
                <div class="compact-party-body">
                    @if($contactDetails ?? null)
                        {!! nl2br($contactDetails) !!}
                    @else
                        {{ $fromName }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="compact-items">
        <thead>
            <tr>
                <th>{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                <th class="compact-num">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                <th class="compact-num">{{ ucfirst(__('laravel-crm::lang.qty')) }}</th>
                <th class="compact-num">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
                <th>{{ ucfirst(__('laravel-crm::lang.comments')) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderProducts->whereNotNull('product_id')->sortBy([['order', 'asc'], ['created_at', 'asc']]) as $orderProduct)
                <tr>
                    <td>{{ $orderProduct->product->name }}</td>
                    <td class="compact-num">{{ money($orderProduct->price ?? null, $orderProduct->currency) }}</td>
                    <td class="compact-num">{{ $orderProduct->quantity }}</td>
                    <td class="compact-num">{{ money($orderProduct->amount ?? null, $orderProduct->currency) }}</td>
                    <td>{{ $orderProduct->comments }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="compact-totals-wrap">
        <table>
            <tr>
                <td class="compact-totals-spacer"></td>
                <td class="compact-totals">
                    <table>
                        <tr>
                            <td class="compact-totals-label">{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</td>
                            <td class="compact-totals-value">{{ money($order->subtotal, $order->currency) }}</td>
                        </tr>
                        @if($order->discount > 0)
                            <tr>
                                <td class="compact-totals-label">{{ ucfirst(__('laravel-crm::lang.discount')) }}</td>
                                <td class="compact-totals-value">{{ money($order->discount, $order->currency) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="compact-totals-label">{{ ucfirst(__('laravel-crm::lang.tax')) }}</td>
                            <td class="compact-totals-value">{{ money($order->tax, $order->currency) }}</td>
                        </tr>
                        <tr class="compact-totals-final">
                            <td class="compact-totals-label">{{ ucfirst(__('laravel-crm::lang.total')) }}</td>
                            <td class="compact-totals-value">{{ money($order->total, $order->currency) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if($order->description || $order->terms)
        <div class="compact-footer">
            @if($order->description)
                <div class="compact-footer-note">
                    <div class="compact-footer-heading">{{ ucfirst(__('laravel-crm::lang.description')) }}</div>
                    <div class="compact-footer-body">{!! nl2br($order->description) !!}</div>
                </div>
            @endif
            @if($order->terms)
                <div class="compact-footer-note">
                    <div class="compact-footer-heading">{{ ucfirst(__('laravel-crm::lang.terms')) }}</div>
                    <div class="compact-footer-body">{!! nl2br($order->terms) !!}</div>
                </div>
            @endif
        </div>
    @endif
@endsection
