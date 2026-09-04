@extends('laravel-crm::pdfs.compact._layout')

@section('compactContent')
    {{-- Compact header: brand left / title right --}}
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
                <h1 class="compact-doc-title">{{ __('laravel-crm::lang.invoice') }}</h1>
            </td>
        </tr>
    </table>

    <hr class="compact-rule" />

    {{-- Dense two-column meta --}}
    <table class="compact-meta compact-block">
        <tr>
            <td class="compact-meta-label">{{ ucfirst(__('laravel-crm::lang.invoice_number')) }}</td>
            <td class="compact-meta-value">{{ $invoice->xeroInvoice->number ?? $invoice->invoice_id }}</td>
            <td class="compact-meta-label">{{ ucfirst(__('laravel-crm::lang.invoice_date')) }}</td>
            <td class="compact-meta-value">{{ $invoice->issue_date->format($dateFormat) }}</td>
        </tr>
        <tr>
            <td class="compact-meta-label">{{ ucfirst(__('laravel-crm::lang.reference')) }}</td>
            <td class="compact-meta-value">{{ $invoice->xeroInvoice->reference ?? $invoice->reference }}</td>
            <td class="compact-meta-label">{{ ucfirst(__('laravel-crm::lang.due_date')) }}</td>
            <td class="compact-meta-value">{{ $invoice->due_date->format($dateFormat) }}</td>
        </tr>
    </table>

    <table class="compact-parties compact-block">
        <tr>
            <td>
                <div class="compact-party-heading">{{ ucfirst(__('laravel-crm::lang.to')) }}</div>
                <div class="compact-party-body">
                    @if($invoice->organization)
                        {{ $invoice->organization->name }}<br />
                    @endif
                    @isset($invoice->person)
                        {{ $invoice->person->name }}<br />
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
                <th class="compact-num">{{ $taxName }}</th>
                <th class="compact-num">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->invoiceLines->whereNotNull('product_id')->sortBy([['order', 'asc'], ['created_at', 'asc']]) as $invoiceLine)
                <tr>
                    <td>
                        {{ $invoiceLine->product->name ?? null }}
                        @if($invoiceLine->comments)
                            <br /><span style="color:#555555">{{ $invoiceLine->comments }}</span>
                        @endif
                    </td>
                    <td class="compact-num">{{ money($invoiceLine->price ?? null, $invoiceLine->currency) }}</td>
                    <td class="compact-num">{{ $invoiceLine->quantity }}</td>
                    <td class="compact-num">{{ money($invoiceLine->tax_amount ?? null, $invoiceLine->currency) }}</td>
                    <td class="compact-num">{{ money($invoiceLine->amount ?? null, $invoiceLine->currency) }}</td>
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
                            <td class="compact-totals-value">{{ money($invoice->subtotal, $invoice->currency) }}</td>
                        </tr>
                        @if($invoice->discount > 0)
                            <tr>
                                <td class="compact-totals-label">{{ ucfirst(__('laravel-crm::lang.discount')) }}</td>
                                <td class="compact-totals-value">{{ money($invoice->discount, $invoice->currency) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="compact-totals-label">{{ $taxName }}</td>
                            <td class="compact-totals-value">{{ money($invoice->tax, $invoice->currency) }}</td>
                        </tr>
                        <tr class="compact-totals-final">
                            <td class="compact-totals-label">{{ ucfirst(__('laravel-crm::lang.total')) }}</td>
                            <td class="compact-totals-value">{{ money($invoice->total, $invoice->currency) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- Footer note area: single column, terms + payment stacked --}}
    @if($paymentInstructions || $invoice->description || $invoice->terms)
        <div class="compact-footer">
            @if($invoice->description)
                <div class="compact-footer-note">
                    <div class="compact-footer-heading">{{ ucfirst(__('laravel-crm::lang.description')) }}</div>
                    <div class="compact-footer-body">{!! nl2br($invoice->description) !!}</div>
                </div>
            @endif
            @if($paymentInstructions)
                <div class="compact-footer-note">
                    <div class="compact-footer-heading">{{ ucfirst(__('laravel-crm::lang.payment')) }}</div>
                    <div class="compact-footer-body">{!! nl2br($paymentInstructions) !!}</div>
                </div>
            @endif
            @if($invoice->terms)
                <div class="compact-footer-note">
                    <div class="compact-footer-heading">{{ ucfirst(__('laravel-crm::lang.terms')) }}</div>
                    <div class="compact-footer-body">{!! nl2br($invoice->terms) !!}</div>
                </div>
            @endif
        </div>
    @endif
@endsection
