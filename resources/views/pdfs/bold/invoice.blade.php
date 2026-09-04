@extends('laravel-crm::pdfs.bold._layout')

@section('boldContent')
    {{-- Full-width primary-colour header band --}}
    <table class="bold-header-band">
        <tr>
            <td width="60%">
                <h1 class="bold-doc-title">{{ strtoupper(__('laravel-crm::lang.invoice')) }}</h1>
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
        <tr>
            <td class="bold-meta-label">{{ ucfirst(__('laravel-crm::lang.invoice_number')) }}</td>
            <td class="bold-meta-value">{{ $invoice->xeroInvoice->number ?? $invoice->invoice_id }}</td>
        </tr>
        @if($invoice->reference || ($invoice->xeroInvoice && $invoice->xeroInvoice->reference))
            <tr>
                <td class="bold-meta-label">{{ ucfirst(__('laravel-crm::lang.reference')) }}</td>
                <td class="bold-meta-value">{{ $invoice->xeroInvoice->reference ?? $invoice->reference }}</td>
            </tr>
        @endif
        @if($invoice->issue_date)
            <tr>
                <td class="bold-meta-label">{{ ucfirst(__('laravel-crm::lang.invoice_date')) }}</td>
                <td class="bold-meta-value">{{ $invoice->issue_date->format($dateFormat) }}</td>
            </tr>
        @endif
        @if($invoice->due_date)
            <tr>
                <td class="bold-meta-label">{{ ucfirst(__('laravel-crm::lang.due_date')) }}</td>
                <td class="bold-meta-value">{{ $invoice->due_date->format($dateFormat) }}</td>
            </tr>
        @endif
    </table>

    <table class="bold-parties bold-block">
        <tr>
            <td>
                <div class="bold-party-heading">{{ ucfirst(__('laravel-crm::lang.to')) }}</div>
                <div class="bold-party-body">
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
                <div class="bold-party-heading">{{ ucfirst(__('laravel-crm::lang.from')) }}</div>
                <div class="bold-party-body">
                    @if($contactDetails)
                        {!! nl2br($contactDetails) !!}
                    @else
                        {{ $fromName }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    @if($invoice->description)
        <div class="bold-note bold-block">
            <div class="bold-note-heading">{{ ucfirst(__('laravel-crm::lang.description')) }}</div>
            <div class="bold-note-body">{!! nl2br($invoice->description) !!}</div>
        </div>
    @endif

    <table class="bold-items">
        <thead>
            <tr>
                <th>{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                <th class="bold-num">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                <th class="bold-num">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
                <th class="bold-num">{{ $taxName }}</th>
                <th class="bold-num">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
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
                    <td class="bold-num">{{ money($invoiceLine->price ?? null, $invoiceLine->currency) }}</td>
                    <td class="bold-num">{{ $invoiceLine->quantity }}</td>
                    <td class="bold-num">{{ money($invoiceLine->tax_amount ?? null, $invoiceLine->currency) }}</td>
                    <td class="bold-num">{{ money($invoiceLine->amount ?? null, $invoiceLine->currency) }}</td>
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
                            <td class="bold-totals-value">{{ money($invoice->subtotal, $invoice->currency) }}</td>
                        </tr>
                        @if($invoice->discount > 0)
                            <tr>
                                <td class="bold-totals-label">{{ ucfirst(__('laravel-crm::lang.discount')) }}</td>
                                <td class="bold-totals-value">{{ money($invoice->discount, $invoice->currency) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="bold-totals-label">{{ $taxName }}</td>
                            <td class="bold-totals-value">{{ money($invoice->tax, $invoice->currency) }}</td>
                        </tr>
                        <tr class="bold-totals-final">
                            <td class="bold-totals-label">{{ ucfirst(__('laravel-crm::lang.total')) }}</td>
                            <td class="bold-totals-value">{{ money($invoice->total, $invoice->currency) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if($paymentInstructions)
        <div class="bold-note bold-block">
            <div class="bold-note-heading">{{ ucfirst(__('laravel-crm::lang.payment')) }}</div>
            <div class="bold-note-body">{!! nl2br($paymentInstructions) !!}</div>
        </div>
    @endif

    @if($invoice->terms)
        <div class="bold-note bold-block">
            <div class="bold-note-heading">{{ ucfirst(__('laravel-crm::lang.terms')) }}</div>
            <div class="bold-note-body">{!! nl2br($invoice->terms) !!}</div>
        </div>
    @endif
@endsection
