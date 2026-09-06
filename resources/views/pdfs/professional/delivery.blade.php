@extends('laravel-crm::pdfs.professional._layout')

@section('professionalContent')
    <table class="prof-header prof-block">
        <tr>
            <td width="45%">
                <h1 class="prof-doc-title">{{ ucfirst(__('laravel-crm::lang.delivery')) }}</h1>
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
        @if($order->reference)
            <tr>
                <td class="prof-meta-label">{{ ucfirst(__('laravel-crm::lang.reference')) }}</td>
                <td class="prof-meta-value">{{ $order->reference }}</td>
            </tr>
        @endif
        @if($delivery->delivery_expected)
            <tr>
                <td class="prof-meta-label">{{ ucwords(__('laravel-crm::lang.delivery_date')) }}</td>
                <td class="prof-meta-value">{{ $delivery->delivery_expected }}</td>
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
                <div class="prof-party-heading">{{ ucfirst(__('laravel-crm::lang.from')) }}</div>
                <div class="prof-party-body">
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
        <div class="prof-note prof-block">
            <div class="prof-note-heading">{{ ucfirst(__('laravel-crm::lang.description')) }}</div>
            <div class="prof-note-body">{!! nl2br($order->description) !!}</div>
        </div>
    @endif

    <table class="prof-items">
        <thead>
            <tr>
                <th>{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                <th class="prof-num">{{ ucfirst(__('laravel-crm::lang.qty')) }}</th>
                <th>{{ ucfirst(__('laravel-crm::lang.comments')) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($delivery->deliveryProducts->where('quantity', '>', 0)->sortBy([['order', 'asc'], ['created_at', 'asc']]) as $deliveryProduct)
                <tr>
                    <td>{{ $deliveryProduct->orderProduct?->product?->name }}</td>
                    <td class="prof-num">{{ $deliveryProduct->quantity }}</td>
                    <td>{{ $deliveryProduct->orderProduct?->comments }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($order->terms)
        <div class="prof-note prof-block">
            <div class="prof-note-heading">{{ ucfirst(__('laravel-crm::lang.terms')) }}</div>
            <div class="prof-note-body">{!! nl2br($order->terms) !!}</div>
        </div>
    @endif

    {{-- Sign-off block --}}
    <div class="prof-note prof-block" style="margin-top: 32px">
        <div class="prof-note-heading">{{ ucfirst(__('laravel-crm::lang.signature')) }}</div>
        <table style="margin-top: 8px">
            <tr>
                <td style="border-bottom: 1px solid #2b2b2b; width: 33%; padding: 24px 8px 4px 8px">
                    <span style="color:#7a7a7a; font-size:10.5px">{{ ucfirst(__('laravel-crm::lang.received_by')) }}</span>
                </td>
                <td style="width: 8px"></td>
                <td style="border-bottom: 1px solid #2b2b2b; width: 33%; padding: 24px 8px 4px 8px">
                    <span style="color:#7a7a7a; font-size:10.5px">{{ ucfirst(__('laravel-crm::lang.received_date')) }}</span>
                </td>
                <td style="width: 8px"></td>
                <td style="border-bottom: 1px solid #2b2b2b; width: 33%; padding: 24px 8px 4px 8px">
                    <span style="color:#7a7a7a; font-size:10.5px">{{ ucfirst(__('laravel-crm::lang.signature')) }}</span>
                </td>
            </tr>
        </table>
    </div>
@endsection
