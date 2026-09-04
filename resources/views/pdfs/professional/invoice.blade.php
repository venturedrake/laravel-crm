@extends('laravel-crm::pdfs.professional._layout')

@section('professionalContent')
    {{-- Header: large title left / small logo right --}}
    <table class="prof-header prof-block">
        <tr>
            <td width="45%">
                <h1 class="prof-doc-title">{{ ucfirst(__('laravel-crm::lang.invoice')) }}</h1>
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

    {{-- Stacked meta rows with thin underlines --}}
    <table class="prof-meta prof-block">
        <tr>
            <td class="prof-meta-label">{{ ucfirst(__('laravel-crm::lang.invoice_number')) }}</td>
            <td class="prof-meta-value">{{ $invoice->xeroInvoice->number ?? $invoice->invoice_id }}</td>
        </tr>
        @if($invoice->reference || ($invoice->xeroInvoice && $invoice->xeroInvoice->reference))
            <tr>
                <td class="prof-meta-label">{{ ucfirst(__('laravel-crm::lang.reference')) }}</td>
                <td class="prof-meta-value">{{ $invoice->xeroInvoice->reference ?? $invoice->reference }}</td>
            </tr>
        @endif
        @if($invoice->issue_date)
            <tr>
                <td class="prof-meta-label">{{ ucfirst(__('laravel-crm::lang.invoice_date')) }}</td>
                <td class="prof-meta-value">{{ $invoice->issue_date->format($dateFormat) }}</td>
            </tr>
        @endif
        @if($invoice->due_date)
            <tr>
                <td class="prof-meta-label">{{ ucfirst(__('laravel-crm::lang.due_date')) }}</td>
                <td class="prof-meta-value">{{ $invoice->due_date->format($dateFormat) }}</td>
            </tr>
        @endif
    </table>

    {{-- Parties block separated by thin rules --}}
    <table class="prof-parties">
        <tr>
            <td>
                <div class="prof-party-heading">{{ ucfirst(__('laravel-crm::lang.to')) }}</div>
                <div class="prof-party-body">
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

    @if($invoice->description)
        <div class="prof-note prof-block">
            <div class="prof-note-heading">{{ ucfirst(__('laravel-crm::lang.description')) }}</div>
            <div class="prof-note-body">{!! nl2br($invoice->description) !!}</div>
        </div>
    @endif

    <table class="prof-items">
        <thead>
            <tr>
                <th>{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                <th class="prof-num">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                <th class="prof-num">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
                <th class="prof-num">{{ $taxName }}</th>
                <th class="prof-num">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->invoiceLines->whereNotNull('product_id')->sortBy([['order', 'asc'], ['created_at', 'asc']]) as $invoiceLine)
                <tr>
                    <td>
                        {{ $invoiceLine->product->name ?? null }}
                        @if($invoiceLine->comments)
                            <br /><span style="color:#7a7a7a">{{ $invoiceLine->comments }}</span>
                        @endif
                    </td>
                    <td class="prof-num">{{ money($invoiceLine->price ?? null, $invoiceLine->currency) }}</td>
                    <td class="prof-num">{{ $invoiceLine->quantity }}</td>
                    <td class="prof-num">{{ money($invoiceLine->tax_amount ?? null, $invoiceLine->currency) }}</td>
                    <td class="prof-num">{{ money($invoiceLine->amount ?? null, $invoiceLine->currency) }}</td>
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
                            <td class="prof-totals-value">{{ money($invoice->subtotal, $invoice->currency) }}</td>
                        </tr>
                        @if($invoice->discount > 0)
                            <tr>
                                <td class="prof-totals-label">{{ ucfirst(__('laravel-crm::lang.discount')) }}</td>
                                <td class="prof-totals-value">{{ money($invoice->discount, $invoice->currency) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="prof-totals-label">{{ $taxName }}</td>
                            <td class="prof-totals-value">{{ money($invoice->tax, $invoice->currency) }}</td>
                        </tr>
                        <tr class="prof-totals-final">
                            <td class="prof-totals-label">{{ ucfirst(__('laravel-crm::lang.total')) }}</td>
                            <td class="prof-totals-value">{{ money($invoice->total, $invoice->currency) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if($paymentInstructions)
        <div class="prof-note prof-block">
            <div class="prof-note-heading">{{ ucfirst(__('laravel-crm::lang.payment')) }}</div>
            <div class="prof-note-body">{!! nl2br($paymentInstructions) !!}</div>
        </div>
    @endif

    @if($invoice->terms)
        <div class="prof-note prof-block">
            <div class="prof-note-heading">{{ ucfirst(__('laravel-crm::lang.terms')) }}</div>
            <div class="prof-note-body">{!! nl2br($invoice->terms) !!}</div>
        </div>
    @endif
@endsection
