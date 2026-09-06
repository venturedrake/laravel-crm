@extends('laravel-crm::pdfs.professional._layout')

@section('professionalContent')
    <table class="prof-header prof-block">
        <tr>
            <td width="45%">
                <h1 class="prof-doc-title">{{ ucfirst(__('laravel-crm::lang.quote')) }}</h1>
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
        @if($quote->quote_id)
            <tr>
                <td class="prof-meta-label">{{ ucfirst(__('laravel-crm::lang.number')) }}</td>
                <td class="prof-meta-value">{{ $quote->quote_id }}</td>
            </tr>
        @endif
        @if($quote->reference)
            <tr>
                <td class="prof-meta-label">{{ ucfirst(__('laravel-crm::lang.reference')) }}</td>
                <td class="prof-meta-value">{{ $quote->reference }}</td>
            </tr>
        @endif
        @if($quote->issue_at)
            <tr>
                <td class="prof-meta-label">{{ ucfirst(__('laravel-crm::lang.issue_date')) }}</td>
                <td class="prof-meta-value">{{ $quote->issue_at->format($dateFormat) }}</td>
            </tr>
        @endif
        @if($quote->expire_at)
            <tr>
                <td class="prof-meta-label">{{ ucfirst(__('laravel-crm::lang.expiry_date')) }}</td>
                <td class="prof-meta-value">{{ $quote->expire_at->format($dateFormat) }}</td>
            </tr>
        @endif
    </table>

    <table class="prof-parties">
        <tr>
            <td>
                <div class="prof-party-heading">{{ ucfirst(__('laravel-crm::lang.issued_to')) }}</div>
                <div class="prof-party-body">
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

    @if($quote->description)
        <div class="prof-note prof-block">
            <div class="prof-note-heading">{{ ucfirst(__('laravel-crm::lang.description')) }}</div>
            <div class="prof-note-body">{!! nl2br($quote->description) !!}</div>
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
            @foreach($quote->quoteProducts->whereNotNull('product_id')->sortBy([['order', 'asc'], ['created_at', 'asc']]) as $quoteProduct)
                <tr>
                    <td>{{ $quoteProduct->product?->name }}</td>
                    <td class="prof-num">{{ money($quoteProduct->price ?? null, $quoteProduct->currency) }}</td>
                    <td class="prof-num">{{ $quoteProduct->quantity }}</td>
                    <td class="prof-num">{{ money($quoteProduct->amount ?? null, $quoteProduct->currency) }}</td>
                    <td>{{ $quoteProduct->comments }}</td>
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
                            <td class="prof-totals-value">{{ money($quote->subtotal, $quote->currency) }}</td>
                        </tr>
                        @if($quote->discount > 0)
                            <tr>
                                <td class="prof-totals-label">{{ ucfirst(__('laravel-crm::lang.discount')) }}</td>
                                <td class="prof-totals-value">{{ money($quote->discount, $quote->currency) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="prof-totals-label">{{ ucfirst(__('laravel-crm::lang.tax')) }}</td>
                            <td class="prof-totals-value">{{ money($quote->tax, $quote->currency) }}</td>
                        </tr>
                        <tr class="prof-totals-final">
                            <td class="prof-totals-label">{{ ucfirst(__('laravel-crm::lang.total')) }}</td>
                            <td class="prof-totals-value">{{ money($quote->total, $quote->currency) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if($quote->terms)
        <div class="prof-note prof-block">
            <div class="prof-note-heading">{{ ucfirst(__('laravel-crm::lang.terms')) }}</div>
            <div class="prof-note-body">{!! nl2br($quote->terms) !!}</div>
        </div>
    @endif
@endsection
