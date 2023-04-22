@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ ucfirst(__('laravel-crm::lang.invoices')) }}
        @endslot

        @slot('actions')
            @include('laravel-crm::partials.filters', [
                'action' => route('laravel-crm.invoices.filter'),
                'model' => '\VentureDrake\LaravelCrm\Models\Invoice'
            ])
            @can('create crm invoices')
            <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.invoices.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_invoice')) }}</a></span>
            @endcan
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-table')
        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.number')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.reference')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.to')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.date')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.due_date')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.overdue_by')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.paid_date')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.paid')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.due')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.sent')) }}</th>
                <th scope="col" width="280"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($invoices as $invoice)
               <tr @if(! $invoice->xeroInvoice) class="has-link" data-url="{{ url(route('laravel-crm.invoices.show', $invoice)) }}" @endif>
                   <td>{{ $invoice->xeroInvoice->number ?? $invoice->invoice_id }}</td>
                   <td>{{ $invoice->xeroInvoice->reference ?? $invoice->reference }}</td>
                   <td>
                       {{ $invoice->organisation->name ?? null }}
                       @if($invoice->person)
                           <br /><small>{{ $invoice->person->name }}</small>
                       @endif    
                   </td>
                   <td>{{ $invoice->issue_date->format($dateFormat) }}</td>
                   <td>{{ $invoice->due_date->format($dateFormat) }}</td>
                   <td class="text-danger">
                       @if(! $invoice->fully_paid_at && $invoice->due_date < \Carbon\Carbon::now())
                           {{ $invoice->due_date->diffForHumans() }}
                       @endif    
                   </td>
                   <td>{{ ($invoice->fully_paid_at) ? $invoice->fully_paid_at->format($dateFormat) : null }}</td>
                   <td>{{ money($invoice->amount_paid, $invoice->currency) }}</td>
                   <td>{{ money($invoice->amount_due, $invoice->currency) }}</td>
                   <td></td>
                    <td class="disable-link text-right">
                        @if(! $invoice->xeroInvoice)
                            @livewire('send-invoice',[
                                'invoice' => $invoice
                            ])
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('laravel-crm.invoices.download', $invoice) }}"><span class="fa fa-download" aria-hidden="true"></span></a>
                            @if(! $invoice->fully_paid_at)
                                @livewire('pay-invoice',[
                                    'invoice' => $invoice
                                ]) 
                            @endif
                            @can('view crm invoices')
                            <a href="{{ route('laravel-crm.invoices.show',$invoice) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                            @endcan
                            @if($invoice->amount_paid <= 0)
                                @can('edit crm invoices')
                                <a href="{{ route('laravel-crm.invoices.edit',$invoice) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                                @endcan
                                @can('delete crm invoices')
                                <form action="{{ route('laravel-crm.invoices.destroy',$invoice) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                    {{ method_field('DELETE') }}
                                    {{ csrf_field() }}
                                    <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.invoice') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                                </form>
                                @endcan
                            @endif     
                        @else
                            <img src="/vendor/laravel-crm/img/xero-icon.png" height="30" />
                        @endif    
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

    @endcomponent

    @if($invoices instanceof \Illuminate\Pagination\LengthAwarePaginator )
        @component('laravel-crm::components.card-footer')
            {{ $invoices->links() }}
        @endcomponent
    @endif

@endcomponent
