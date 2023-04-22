@extends('laravel-crm::layouts.document')

@section('content')

    <table class="table table-sm table-items">
        <tbody>
            <tr>
                <td width="50%"> 
                    <h1>{{ strtoupper(__('laravel-crm::lang.invoice')) }}</h1>
                    @if($invoice->reference)
                        <p><strong>{{ ucfirst(__('laravel-crm::lang.reference')) }}</strong> {{ $invoice->reference }}</p>
                    @endif
                    <p><strong>{{ ucfirst(__('laravel-crm::lang.invoice_date')) }}</strong> {{ $invoice->issue_date->format($dateFormat) }}</p>
                    <p><strong>{{ ucfirst(__('laravel-crm::lang.invoice_number')) }}</strong> {{ $invoice->invoice_id  }}</p>
                    <p><strong>{{ ucfirst(__('laravel-crm::lang.due_date')) }}</strong> {{ $invoice->due_date->format($dateFormat) }}</p>
                </td>
                <td width="50%" style="text-align: right">
                    @if($logo)
                        <img src="{{ asset('storage/'.$logo) }}" height="140" />
                    @endif
                </td>
            </tr>
            <tr>
                <td>
                    <strong>{{ ucfirst(__('laravel-crm::lang.to')) }}</strong><br />
                    {{ $invoice->organisation->name ?? $invoice->organisation->person->name }}<br />
                    @isset($invoice->person)
                        {{ $invoice->person->name }}<br />
                    @endisset
                    @if(isset($organisation_address))
                        @if($organisation_address->line1)
                            {{ $organisation_address->line1 }}<br />
                        @endif
                        @if($organisation_address->line2)
                            {{ $organisation_address->line2 }}<br />
                        @endif
                        @if($organisation_address->line3)
                            {{ $organisation_address->line3 }}<br />
                        @endif
                        @if($organisation_address->city || $organisation_address->state || $organisation_address->postcode)
                            {{ $organisation_address->city }} {{ $organisation_address->state }} {{ $organisation_address->postcode }}<br />
                        @endif
                        {{ $organisation_address->country }}
                    @elseif($address)
                        {{ $address->line1 }}<br />
                        @if($address->line2)
                            {{ $address->line2 }}<br />
                        @endif
                        @if($address->line3)
                            {{ $address->line3 }}<br />
                        @endif
                        {{ $address->city }}<br />
                        {{ $address->country }}
                    @endif
                </td>
                
                <td>
                    <strong>{{ ucfirst(__('laravel-crm::lang.from')) }}</strong><br />
                    {{ $fromName }}<br />
                    {{-- 19-21 South Steyne<br />
                     MANLY NSW 2095<br />
                     Australia--}}
                </td>
            </tr>
        </tbody>
    </table>
    @if($invoice->description)
        <table class="table table-bordered table-sm table-items">
          <tbody>
            <tr>
                <td><h4>{{ ucfirst(__('laravel-crm::lang.description')) }}</h4>
                    {!! nl2br($invoice->description) !!}</td>
            </tr>
          </tbody>  
        </table>
    @endif
    <table class="table table-bordered table-sm table-items">
        <thead>
        <tr>
            <th scope="col">{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
            <th scope="col">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
            <th scope="col">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
            <th scope="col">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
            <th scope="col">{{ ucfirst(__('laravel-crm::lang.comments')) }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($invoice->invoiceLInes()->whereNotNull('product_id')->get() as $invoiceLine)
            <tr>
                <td>{{ $invoiceLine->product->name }}</td>
                <td>{{ money($invoiceLine->price ?? null, $invoiceLine->currency) }}</td>
                <td>{{ $invoiceLine->quantity }}</td>
                <td>{{ money($invoiceLine->amount ?? null, $invoiceLine->currency) }}</td>
                <td>{{ $invoiceLine->comments }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td></td>
            <td></td>
            <td><strong>{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</strong></td>
            <td>{{ money($invoice->subtotal, $invoice->currency) }}</td>
            <td></td>
        </tr>
        @if($invoice->discount > 0)
            <tr>
                <td></td>
                <td></td>
                <td><strong>{{ ucfirst(__('laravel-crm::lang.discount')) }}</strong></td>
                <td>{{ money($invoice->discount, $invoice->currency) }}</td>
                <td></td>
            </tr>
        @endif
        <tr>
            <td></td>
            <td></td>
            <td><strong>{{ ucfirst(__('laravel-crm::lang.tax')) }}</strong></td>
            <td>{{ money($invoice->tax, $invoice->currency) }}</td>
            <td></td>
        </tr>
        {{--<tr>
            <td></td>
            <td></td>
            <td><strong>{{ ucfirst(__('laravel-crm::lang.adjustment')) }}</strong></td>
            <td>{{ money($invoice->adjustments, $invoice->currency) }}</td>
            <td></td>
        </tr>--}}
        <tr>
            <td></td>
            <td></td>
            <td><strong>{{ ucfirst(__('laravel-crm::lang.total')) }}</strong></td>
            <td>{{ money($invoice->total, $invoice->currency) }}</td>
            <td></td>
        </tr>
        </tfoot>
    </table>
    @if($invoice->terms)
        <table class="table table-bordered table-sm table-items">
            <tbody>
            <tr>
                <td>
                    <h4>{{ ucfirst(__('laravel-crm::lang.terms')) }}</h4>
                    {!! nl2br($invoice->terms) !!}
                </td>
            </tr>
            </tbody>
        </table>
    @endif
@endsection
