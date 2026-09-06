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
                <span class="modern-pill">{{ __('laravel-crm::lang.quote') }}</span>
            </td>
        </tr>
    </table>

    <hr class="modern-hair" />

    <table class="modern-meta modern-block">
        @if($quote->quote_id)
            <tr>
                <td class="modern-meta-label">{{ ucfirst(__('laravel-crm::lang.number')) }}</td>
                <td>{{ $quote->quote_id }}</td>
            </tr>
        @endif
        @if($quote->reference)
            <tr>
                <td class="modern-meta-label">{{ ucfirst(__('laravel-crm::lang.reference')) }}</td>
                <td>{{ $quote->reference }}</td>
            </tr>
        @endif
        @if($quote->issue_at)
            <tr>
                <td class="modern-meta-label">{{ ucfirst(__('laravel-crm::lang.issue_date')) }}</td>
                <td>{{ $quote->issue_at->format($dateFormat) }}</td>
            </tr>
        @endif
        @if($quote->expire_at)
            <tr>
                <td class="modern-meta-label">{{ ucfirst(__('laravel-crm::lang.expiry_date')) }}</td>
                <td>{{ $quote->expire_at->format($dateFormat) }}</td>
            </tr>
        @endif
    </table>

    <table class="modern-parties modern-block">
        <tr>
            <td>
                <div class="modern-party-heading">{{ ucfirst(__('laravel-crm::lang.issued_to')) }}</div>
                <div class="modern-party-body">
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

    @if($quote->description)
        <div class="modern-note modern-block">
            <div class="modern-note-heading">{{ ucfirst(__('laravel-crm::lang.description')) }}</div>
            <div class="modern-note-body">{!! nl2br($quote->description) !!}</div>
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
            @foreach($quote->quoteProducts->whereNotNull('product_id')->sortBy([['order', 'asc'], ['created_at', 'asc']]) as $quoteProduct)
                <tr>
                    <td>{{ $quoteProduct->product?->name }}</td>
                    <td class="modern-num">{{ money($quoteProduct->price ?? null, $quoteProduct->currency) }}</td>
                    <td class="modern-num">{{ $quoteProduct->quantity }}</td>
                    <td class="modern-num">{{ money($quoteProduct->amount ?? null, $quoteProduct->currency) }}</td>
                    <td>{{ $quoteProduct->comments }}</td>
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
                            <td class="modern-totals-value">{{ money($quote->subtotal, $quote->currency) }}</td>
                        </tr>
                        @if($quote->discount > 0)
                            <tr>
                                <td class="modern-totals-label">{{ ucfirst(__('laravel-crm::lang.discount')) }}</td>
                                <td class="modern-totals-value">{{ money($quote->discount, $quote->currency) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="modern-totals-label">{{ ucfirst(__('laravel-crm::lang.tax')) }}</td>
                            <td class="modern-totals-value">{{ money($quote->tax, $quote->currency) }}</td>
                        </tr>
                        <tr class="modern-totals-final">
                            <td class="modern-totals-label">{{ ucfirst(__('laravel-crm::lang.total')) }}</td>
                            <td class="modern-totals-value">{{ money($quote->total, $quote->currency) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if($quote->terms)
        <div class="modern-note modern-block">
            <div class="modern-note-heading">{{ ucfirst(__('laravel-crm::lang.terms')) }}</div>
            <div class="modern-note-body">{!! nl2br($quote->terms) !!}</div>
        </div>
    @endif
@endsection
