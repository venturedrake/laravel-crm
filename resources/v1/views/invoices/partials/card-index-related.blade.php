@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-table')
        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.number')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.reference')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.date')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.due_date')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.overdue_by')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.paid_date')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.paid')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.due')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.sent')) }}</th>
                <th scope="col"></th> 
            </tr>
            </thead>
            <tbody>
            @foreach($invoices as $invoice)
               <tr @if(! $invoice->xeroInvoice) class="has-link" data-url="{{ url(route('laravel-crm.invoices.show', $invoice)) }}" @endif>
                   <td>{{ $invoice->xeroInvoice->number ?? $invoice->invoice_id }}</td>
                   <td>{{ $invoice->xeroInvoice->reference ?? $invoice->reference }}</td>
                   <td>{{ $invoice->issue_date->format($dateFormat) }}</td>
                   <td>{{ $invoice->due_date->format($dateFormat) }}</td>
                   <td class="text-danger">
                       @if(! $invoice->fully_paid_at && $invoice->due_date->diffinDays() > 0 && $invoice->due_date < \Carbon\Carbon::now()->timezone($timezone))
                           {{ $invoice->due_date->diffForHumans(false, true) }}
                       @endif
                   </td>
                   <td>{{ ($invoice->fully_paid_at) ? $invoice->fully_paid_at->format($dateFormat) : null }}</td>
                   <td>{{ money($invoice->amount_paid, $invoice->currency) }}</td>
                   <td>{{ money($invoice->amount_due, $invoice->currency) }}</td>
                   <td>
                       @if($invoice->sent == 1)
                           <span class="text-success">Sent</span>
                       @endif
                   </td>
                   <td>
                   @if($invoice->xeroInvoice)
                    <img src="/vendor/laravel-crm/img/xero-icon.png" height="30" />
                   @endif
                   </td>
                </tr>
            @endforeach
            </tbody>
        </table>

    @endcomponent

@endcomponent
