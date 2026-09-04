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
                <span class="modern-pill">{{ __('laravel-crm::lang.delivery') }}</span>
            </td>
        </tr>
    </table>

    <hr class="modern-hair" />

    <table class="modern-meta modern-block">
        @if($order->reference)
            <tr>
                <td class="modern-meta-label">{{ ucfirst(__('laravel-crm::lang.reference')) }}</td>
                <td>{{ $order->reference }}</td>
            </tr>
        @endif
        @if($delivery->delivery_expected)
            <tr>
                <td class="modern-meta-label">{{ ucwords(__('laravel-crm::lang.delivery_date')) }}</td>
                <td>{{ $delivery->delivery_expected }}</td>
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
                <div class="modern-party-heading">{{ ucfirst(__('laravel-crm::lang.from')) }}</div>
                <div class="modern-party-body">
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
        <div class="modern-note modern-block">
            <div class="modern-note-heading">{{ ucfirst(__('laravel-crm::lang.description')) }}</div>
            <div class="modern-note-body">{!! nl2br($order->description) !!}</div>
        </div>
    @endif

    <table class="modern-items">
        <thead>
            <tr>
                <th>{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                <th class="modern-num">{{ ucfirst(__('laravel-crm::lang.qty')) }}</th>
                <th>{{ ucfirst(__('laravel-crm::lang.comments')) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($delivery->deliveryProducts->where('quantity', '>', 0)->sortBy([['order', 'asc'], ['created_at', 'asc']]) as $deliveryProduct)
                <tr>
                    <td>{{ $deliveryProduct->orderProduct->product->name }}</td>
                    <td class="modern-num">{{ $deliveryProduct->quantity }}</td>
                    <td>{{ $deliveryProduct->orderProduct->comments }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($order->terms)
        <div class="modern-note modern-block">
            <div class="modern-note-heading">{{ ucfirst(__('laravel-crm::lang.terms')) }}</div>
            <div class="modern-note-body">{!! nl2br($order->terms) !!}</div>
        </div>
    @endif

    {{-- Sign-off block mirrors the classic delivery layout --}}
    <div class="modern-note modern-block" style="margin-top: 32px">
        <div class="modern-note-heading">{{ ucfirst(__('laravel-crm::lang.signature')) }}</div>
        <table style="margin-top: 8px">
            <tr>
                <td style="border-bottom: 1px solid #d1d5db; width: 33%; padding: 24px 8px 4px 8px">
                    <span style="color:#6b7280; font-size:10.5px">{{ ucfirst(__('laravel-crm::lang.received_by')) }}</span>
                </td>
                <td style="width: 8px"></td>
                <td style="border-bottom: 1px solid #d1d5db; width: 33%; padding: 24px 8px 4px 8px">
                    <span style="color:#6b7280; font-size:10.5px">{{ ucfirst(__('laravel-crm::lang.received_date')) }}</span>
                </td>
                <td style="width: 8px"></td>
                <td style="border-bottom: 1px solid #d1d5db; width: 33%; padding: 24px 8px 4px 8px">
                    <span style="color:#6b7280; font-size:10.5px">{{ ucfirst(__('laravel-crm::lang.signature')) }}</span>
                </td>
            </tr>
        </table>
    </div>
@endsection
