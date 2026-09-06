@extends('laravel-crm::pdfs.bold._layout')

@section('boldContent')
    <table class="bold-header-band">
        <tr>
            <td width="60%">
                <h1 class="bold-doc-title">{{ strtoupper(__('laravel-crm::lang.quote')) }}</h1>
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
        @if($quote->quote_id)
            <tr>
                <td class="bold-meta-label">{{ ucfirst(__('laravel-crm::lang.number')) }}</td>
                <td class="bold-meta-value">{{ $quote->quote_id }}</td>
            </tr>
        @endif
        @if($quote->reference)
            <tr>
                <td class="bold-meta-label">{{ ucfirst(__('laravel-crm::lang.reference')) }}</td>
                <td class="bold-meta-value">{{ $quote->reference }}</td>
            </tr>
        @endif
        @if($quote->issue_at)
            <tr>
                <td class="bold-meta-label">{{ ucfirst(__('laravel-crm::lang.issue_date')) }}</td>
                <td class="bold-meta-value">{{ $quote->issue_at->format($dateFormat) }}</td>
            </tr>
        @endif
        @if($quote->expire_at)
            <tr>
                <td class="bold-meta-label">{{ ucfirst(__('laravel-crm::lang.expiry_date')) }}</td>
                <td class="bold-meta-value">{{ $quote->expire_at->format($dateFormat) }}</td>
            </tr>
        @endif
    </table>

    <table class="bold-parties bold-block">
        <tr>
            <td>
                <div class="bold-party-heading">{{ ucfirst(__('laravel-crm::lang.issued_to')) }}</div>
                <div class="bold-party-body">
                    {{ $quote->organization->name ?? $quote->organization->person->name ?? null }}<br />
                    @isset($quote->person)
                        {{ $quote->person->name }}<br />
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

    @if($quote->description)
        <div class="bold-note bold-block">
            <div class="bold-note-heading">{{ ucfirst(__('laravel-crm::lang.description')) }}</div>
            <div class="bold-note-body">{!! nl2br($quote->description) !!}</div>
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
            @foreach($quote->quoteProducts->whereNotNull('product_id')->sortBy([['order', 'asc'], ['created_at', 'asc']]) as $quoteProduct)
                <tr>
                    <td>{{ $quoteProduct->product?->name }}</td>
                    <td class="bold-num">{{ money($quoteProduct->price ?? null, $quoteProduct->currency) }}</td>
                    <td class="bold-num">{{ $quoteProduct->quantity }}</td>
                    <td class="bold-num">{{ money($quoteProduct->amount ?? null, $quoteProduct->currency) }}</td>
                    <td>{{ $quoteProduct->comments }}</td>
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
                            <td class="bold-totals-value">{{ money($quote->subtotal, $quote->currency) }}</td>
                        </tr>
                        @if($quote->discount > 0)
                            <tr>
                                <td class="bold-totals-label">{{ ucfirst(__('laravel-crm::lang.discount')) }}</td>
                                <td class="bold-totals-value">{{ money($quote->discount, $quote->currency) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="bold-totals-label">{{ ucfirst(__('laravel-crm::lang.tax')) }}</td>
                            <td class="bold-totals-value">{{ money($quote->tax, $quote->currency) }}</td>
                        </tr>
                        <tr class="bold-totals-final">
                            <td class="bold-totals-label">{{ ucfirst(__('laravel-crm::lang.total')) }}</td>
                            <td class="bold-totals-value">{{ money($quote->total, $quote->currency) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if($quote->terms)
        <div class="bold-note bold-block">
            <div class="bold-note-heading">{{ ucfirst(__('laravel-crm::lang.terms')) }}</div>
            <div class="bold-note-body">{!! nl2br($quote->terms) !!}</div>
        </div>
    @endif
@endsection
