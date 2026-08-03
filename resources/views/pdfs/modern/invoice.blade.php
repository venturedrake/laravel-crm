@extends('laravel-crm::pdfs.modern._layout')

@section('modernContent')
    {{-- Header: small brand top-left + TAX INVOICE-style pill top-right --}}
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
                <span class="modern-pill">{{ __('laravel-crm::lang.invoice') }}</span>
            </td>
        </tr>
    </table>

    <hr class="modern-hair" />

    {{-- Meta block: invoice number, dates, reference --}}
    <table class="modern-meta modern-block">
        <tr>
            <td class="modern-meta-label">{{ ucfirst(__('laravel-crm::lang.invoice_number')) }}</td>
            <td>{{ $invoice->xeroInvoice->number ?? $invoice->invoice_id }}</td>
        </tr>
        @if($invoice->reference || ($invoice->xeroInvoice && $invoice->xeroInvoice->reference))
            <tr>
                <td class="modern-meta-label">{{ ucfirst(__('laravel-crm::lang.reference')) }}</td>
                <td>{{ $invoice->xeroInvoice->reference ?? $invoice->reference }}</td>
            </tr>
        @endif
        <tr>
            <td class="modern-meta-label">{{ ucfirst(__('laravel-crm::lang.invoice_date')) }}</td>
            <td>{{ $invoice->issue_date->format($dateFormat) }}</td>
        </tr>
        <tr>
            <td class="modern-meta-label">{{ ucfirst(__('laravel-crm::lang.due_date')) }}</td>
            <td>{{ $invoice->due_date->format($dateFormat) }}</td>
        </tr>
    </table>

    {{-- Parties block: bill-to left, from right --}}
    <table class="modern-parties modern-block">
        <tr>
            <td>
                <div class="modern-party-heading">{{ ucfirst(__('laravel-crm::lang.to')) }}</div>
                <div class="modern-party-body">
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

    @if($invoice->description)
        <div class="modern-note modern-block">
            <div class="modern-note-heading">{{ ucfirst(__('laravel-crm::lang.description')) }}</div>
            <div class="modern-note-body">{!! nl2br(e($invoice->description)) !!}</div>
        </div>
    @endif

    {{-- Line items table with hair-line dividers --}}
    <table class="modern-items">
        <thead>
            <tr>
                <th>{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                <th class="modern-num">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                <th class="modern-num">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
                <th class="modern-num">{{ $taxName }}</th>
                <th class="modern-num">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->invoiceLines->whereNotNull('product_id')->sortBy([['order', 'asc'], ['created_at', 'asc']]) as $invoiceLine)
                <tr>
                    <td>
                        {{ $invoiceLine->product->name ?? null }}
                        @if($invoiceLine->comments)
                            <br /><span style="color:#6b7280">{{ $invoiceLine->comments }}</span>
                        @endif
                    </td>
                    <td class="modern-num">{{ money($invoiceLine->price ?? null, $invoiceLine->currency) }}</td>
                    <td class="modern-num">{{ $invoiceLine->quantity }}</td>
                    <td class="modern-num">{{ money($invoiceLine->tax_amount ?? null, $invoiceLine->currency) }}</td>
                    <td class="modern-num">{{ money($invoiceLine->amount ?? null, $invoiceLine->currency) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Right-aligned totals block --}}
    <div class="modern-totals-wrap">
        <table>
            <tr>
                <td class="modern-totals-spacer"></td>
                <td class="modern-totals">
                    <table>
                        <tr>
                            <td class="modern-totals-label">{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</td>
                            <td class="modern-totals-value">{{ money($invoice->subtotal, $invoice->currency) }}</td>
                        </tr>
                        @if($invoice->discount > 0)
                            <tr>
                                <td class="modern-totals-label">{{ ucfirst(__('laravel-crm::lang.discount')) }}</td>
                                <td class="modern-totals-value">{{ money($invoice->discount, $invoice->currency) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="modern-totals-label">{{ $taxName }}</td>
                            <td class="modern-totals-value">{{ money($invoice->tax, $invoice->currency) }}</td>
                        </tr>
                        <tr class="modern-totals-final">
                            <td class="modern-totals-label">{{ ucfirst(__('laravel-crm::lang.total')) }}</td>
                            <td class="modern-totals-value">{{ money($invoice->total, $invoice->currency) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if($paymentInstructions)
        <div class="modern-note modern-block">
            <div class="modern-note-heading">{{ ucfirst(__('laravel-crm::lang.payment')) }}</div>
            <div class="modern-note-body">{!! nl2br(e($paymentInstructions)) !!}</div>
        </div>
    @endif

    @if($invoice->terms)
        <div class="modern-note modern-block">
            <div class="modern-note-heading">{{ ucfirst(__('laravel-crm::lang.terms')) }}</div>
            <div class="modern-note-body">{!! nl2br(e($invoice->terms)) !!}</div>
        </div>
    @endif
@endsection
