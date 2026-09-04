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
                <h1 class="compact-doc-title">{{ __('laravel-crm::lang.delivery') }}</h1>
            </td>
        </tr>
    </table>

    <hr class="compact-rule" />

    <table class="compact-meta compact-block">
        <tr>
            <td class="compact-meta-label">{{ ucfirst(__('laravel-crm::lang.reference')) }}</td>
            <td class="compact-meta-value">{{ $order->reference }}</td>
            <td class="compact-meta-label">{{ ucwords(__('laravel-crm::lang.delivery_date')) }}</td>
            <td class="compact-meta-value">{{ $delivery->delivery_expected }}</td>
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
                    @if($address && $address->contact)
                        <strong>{{ ucwords(__('laravel-crm::lang.contact')) }}:</strong> {{ $address->contact }}<br />
                    @endif
                    @if($address && $address->phone)
                        <strong>{{ ucwords(__('laravel-crm::lang.phone')) }}:</strong> {{ $address->phone }}<br />
                    @endif
                    @if($address)
                        {{ $address->line1 }}<br />
                        @if($address->line2){{ $address->line2 }}<br />@endif
                        @if($address->line3){{ $address->line3 }}<br />@endif
                        {{ $address->city }}<br />
                        {{ $address->country }}
                    @elseif(isset($organization_address))
                        @if($organization_address->line1){{ $organization_address->line1 }}<br />@endif
                        @if($organization_address->line2){{ $organization_address->line2 }}<br />@endif
                        @if($organization_address->line3){{ $organization_address->line3 }}<br />@endif
                        @if($organization_address->city || $organization_address->state || $organization_address->postcode)
                            {{ $organization_address->city }} {{ $organization_address->state }} {{ $organization_address->postcode }}<br />
                        @endif
                        {{ $organization_address->country }}
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
                <th class="compact-num">{{ ucfirst(__('laravel-crm::lang.qty')) }}</th>
                <th>{{ ucfirst(__('laravel-crm::lang.comments')) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($delivery->deliveryProducts->where('quantity', '>', 0)->sortBy([['order', 'asc'], ['created_at', 'asc']]) as $deliveryProduct)
                <tr>
                    <td>{{ $deliveryProduct->orderProduct->product->name }}</td>
                    <td class="compact-num">{{ $deliveryProduct->quantity }}</td>
                    <td>{{ $deliveryProduct->orderProduct->comments }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

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

        <div class="compact-footer-note" style="margin-top: 12px">
            <div class="compact-footer-heading">{{ ucfirst(__('laravel-crm::lang.signature')) }}</div>
            <table style="margin-top: 4px">
                <tr>
                    <td style="border-bottom: 1px solid #000000; width: 33%; padding: 18px 6px 3px 6px">
                        <span style="color:#555555; font-size:13px">{{ ucfirst(__('laravel-crm::lang.received_by')) }}</span>
                    </td>
                    <td style="width: 6px"></td>
                    <td style="border-bottom: 1px solid #000000; width: 33%; padding: 18px 6px 3px 6px">
                        <span style="color:#555555; font-size:13px">{{ ucfirst(__('laravel-crm::lang.received_date')) }}</span>
                    </td>
                    <td style="width: 6px"></td>
                    <td style="border-bottom: 1px solid #000000; width: 33%; padding: 18px 6px 3px 6px">
                        <span style="color:#555555; font-size:13px">{{ ucfirst(__('laravel-crm::lang.signature')) }}</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
@endsection
