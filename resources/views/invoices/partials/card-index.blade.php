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
            {{--@can('create crm invoices')
            <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.invoices.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_invoice')) }}</a></span>
            @endcan--}}
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-table')

       {{-- <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.labels')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.organization')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.contact')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.sub_total')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.discount')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.tax')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.adjustment')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.total')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.owner')) }}</th>
                <th scope="col" width="240"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($invoices as $invoice)
               <tr class="has-link" data-url="{{ url(route('laravel-crm.invoices.show', $invoice)) }}">
                   <td>{{ $invoice->reference }}</td>
                   <td>@include('laravel-crm::partials.labels',[
                            'labels' => $invoice->labels,
                            'limit' => 3
                        ])</td>
                    <td>{{ $invoice->organisation->name ?? null }}</td>
                    <td>{{ $invoice->person->name ?? null }}</td>
                    <td>{{ money($invoice->subtotal, $invoice->currency) }}</td>
                    <td>{{ money($invoice->discount, $invoice->currency) }}</td>
                    <td>{{ money($invoice->tax, $invoice->currency) }}</td>
                    <td>{{ money($invoice->adjustments, $invoice->currency) }}</td>
                    <td>{{ money($invoice->total, $invoice->currency) }}</td>
                    <td>{{ $invoice->ownerUser->name ?? null }}</td>
                    <td class="disable-link text-right">
                        @can('edit crm invoices')
                            <a href="{{ route('laravel-crm.invoices.invoice',$invoice) }}" class="btn btn-success btn-sm">{{ ucwords(__('laravel-crm::lang.invoice')) }}</a>
                        @endcan
                        @can('view crm invoices')
                        <a href="{{ route('laravel-crm.invoices.show',$invoice) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                        @endcan
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
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>--}}

    @endcomponent

    @if($invoices instanceof \Illuminate\Pagination\LengthAwarePaginator )
        @component('laravel-crm::components.card-footer')
            {{ $invoices->links() }}
        @endcomponent
    @endif

@endcomponent
