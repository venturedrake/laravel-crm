@extends('laravel-crm::pdfs.modern._layout')

@section('modernContent')
    <table class="modern-header">
        <tr>
            <td width="60%">
                @if($logo)
                    <img class="modern-brand-logo" src="{{ str_starts_with($logo, 'data:') ? $logo : asset('storage/'.$logo) }}" alt="" />
                @endif
                @if(! $logo && $fromName)
                    <div class="modern-brand-name">{{ $fromName }}</div>
                @endif
            </td>
            <td width="40%" style="text-align: right">
                <span class="modern-pill">{{ __('laravel-crm::lang.order') }}</span>
            </td>
        </tr>
    </table>

    <hr class="modern-hair" />

    <table class="modern-meta modern-block">
        @if($order->order_id)
            <tr>
                <td class="modern-meta-label">{{ ucfirst(__('laravel-crm::lang.number')) }}</td>
                <td>{{ $order->order_id }}</td>
            </tr>
        @endif
        @if($order->reference)
            <tr>
                <td class="modern-meta-label">{{ ucfirst(__('laravel-crm::lang.reference')) }}</td>
                <td>{{ $order->reference }}</td>
            </tr>
        @endif
    </table>

    <table class="modern-parties modern-block">
        <tr>
            <td>
                <div class="modern-party-heading">{{ ucfirst(__('laravel-crm::lang.to')) }}</div>
                <div class="modern-party-body">
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
                <div class="modern-party-heading">{{ ucfirst(__('laravel-crm::lang.from')) }}</div>
                <div class="modern-party-body">
                    @if($contactDetails)
                        {!! nl2br(e($contactDetails)) !!}
                    @else
                        {{ $fromName }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    @if($order->description)
        <div class="modern-note modern-block">
            <div class="modern-note-heading">{{ ucfirst(__('laravel-crm::lang.description')) }}</div>
            <div class="modern-note-body">{!! nl2br(e($order->description)) !!}</div>
        </div>
    @endif

    <table class="modern-items">
        <thead>
            <tr>
                <th>{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                <th class="modern-num">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                <th class="modern-num">{{ ucfirst(__('laravel-crm::lang.qty')) }}</th>
                <th class="modern-num">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
                <th>{{ ucfirst(__('laravel-crm::lang.comments')) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderProducts->whereNotNull('product_id')->sortBy([['order', 'asc'], ['created_at', 'asc']]) as $orderProduct)
                <tr>
                    <td>{{ $orderProduct->product->name }}</td>
                    <td class="modern-num">{{ money($orderProduct->price ?? null, $orderProduct->currency) }}</td>
                    <td class="modern-num">{{ $orderProduct->quantity }}</td>
                    <td class="modern-num">{{ money($orderProduct->amount ?? null, $orderProduct->currency) }}</td>
                    <td>{{ $orderProduct->comments }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="modern-totals-wrap">
        <table>
            <tr>
                <td class="modern-totals-spacer"></td>
                <td class="modern-totals">
                    <table>
                        <tr>
                            <td class="modern-totals-label">{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</td>
                            <td class="modern-totals-value">{{ money($order->subtotal, $order->currency) }}</td>
                        </tr>
                        @if($order->discount > 0)
                            <tr>
                                <td class="modern-totals-label">{{ ucfirst(__('laravel-crm::lang.discount')) }}</td>
                                <td class="modern-totals-value">{{ money($order->discount, $order->currency) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="modern-totals-label">{{ ucfirst(__('laravel-crm::lang.tax')) }}</td>
                            <td class="modern-totals-value">{{ money($order->tax, $order->currency) }}</td>
                        </tr>
                        <tr class="modern-totals-final">
                            <td class="modern-totals-label">{{ ucfirst(__('laravel-crm::lang.total')) }}</td>
                            <td class="modern-totals-value">{{ money($order->total, $order->currency) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if($order->terms)
        <div class="modern-note modern-block">
            <div class="modern-note-heading">{{ ucfirst(__('laravel-crm::lang.terms')) }}</div>
            <div class="modern-note-body">{!! nl2br(e($order->terms)) !!}</div>
        </div>
    @endif
@endsection
