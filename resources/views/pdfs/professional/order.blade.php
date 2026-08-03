@extends('laravel-crm::pdfs.professional._layout')

@section('professionalContent')
    <table class="prof-header prof-block">
        <tr>
            <td width="45%">
                <h1 class="prof-doc-title">{{ ucfirst(__('laravel-crm::lang.order')) }}</h1>
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
        @if($order->order_id)
            <tr>
                <td class="prof-meta-label">{{ ucfirst(__('laravel-crm::lang.number')) }}</td>
                <td class="prof-meta-value">{{ $order->order_id }}</td>
            </tr>
        @endif
        @if($order->reference)
            <tr>
                <td class="prof-meta-label">{{ ucfirst(__('laravel-crm::lang.reference')) }}</td>
                <td class="prof-meta-value">{{ $order->reference }}</td>
            </tr>
        @endif
    </table>

    <table class="prof-parties">
        <tr>
            <td>
                <div class="prof-party-heading">{{ ucfirst(__('laravel-crm::lang.to')) }}</div>
                <div class="prof-party-body">
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
                <div class="prof-party-heading">{{ ucfirst(__('laravel-crm::lang.from')) }}</div>
                <div class="prof-party-body">
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
        <div class="prof-note prof-block">
            <div class="prof-note-heading">{{ ucfirst(__('laravel-crm::lang.description')) }}</div>
            <div class="prof-note-body">{!! nl2br(e($order->description)) !!}</div>
        </div>
    @endif

    <table class="prof-items">
        <thead>
            <tr>
                <th>{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                <th class="prof-num">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                <th class="prof-num">{{ ucfirst(__('laravel-crm::lang.qty')) }}</th>
                <th class="prof-num">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
                <th>{{ ucfirst(__('laravel-crm::lang.comments')) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderProducts->whereNotNull('product_id')->sortBy([['order', 'asc'], ['created_at', 'asc']]) as $orderProduct)
                <tr>
                    <td>{{ $orderProduct->product->name }}</td>
                    <td class="prof-num">{{ money($orderProduct->price ?? null, $orderProduct->currency) }}</td>
                    <td class="prof-num">{{ $orderProduct->quantity }}</td>
                    <td class="prof-num">{{ money($orderProduct->amount ?? null, $orderProduct->currency) }}</td>
                    <td>{{ $orderProduct->comments }}</td>
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
                            <td class="prof-totals-value">{{ money($order->subtotal, $order->currency) }}</td>
                        </tr>
                        @if($order->discount > 0)
                            <tr>
                                <td class="prof-totals-label">{{ ucfirst(__('laravel-crm::lang.discount')) }}</td>
                                <td class="prof-totals-value">{{ money($order->discount, $order->currency) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="prof-totals-label">{{ ucfirst(__('laravel-crm::lang.tax')) }}</td>
                            <td class="prof-totals-value">{{ money($order->tax, $order->currency) }}</td>
                        </tr>
                        <tr class="prof-totals-final">
                            <td class="prof-totals-label">{{ ucfirst(__('laravel-crm::lang.total')) }}</td>
                            <td class="prof-totals-value">{{ money($order->total, $order->currency) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if($order->terms)
        <div class="prof-note prof-block">
            <div class="prof-note-heading">{{ ucfirst(__('laravel-crm::lang.terms')) }}</div>
            <div class="prof-note-body">{!! nl2br(e($order->terms)) !!}</div>
        </div>
    @endif
@endsection
