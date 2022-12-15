@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ $invoice->title }}
        @endslot

        @slot('actions')
            <span class="float-right">
                @include('laravel-crm::partials.return-button',[
                    'model' => $invoice,
                    'route' => 'invoices'
                ])
                @include('laravel-crm::partials.navs.activities') |
                @can('edit crm invoices')
                <a href="{{ url(route('laravel-crm.invoices.edit', $invoice)) }}" type="button" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                @endcan
                @can('delete crm invoices')
                <form action="{{ route('laravel-crm.invoices.destroy', $invoice) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.invoice') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
                @endcan
            </span>
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-body')

        <div class="row card-show card-fa-w30">
            <div class="col-sm-6 binvoice-right">
                <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.details')) }}</h6>
                <hr />
                <dl class="row">
                    <dt class="col-sm-3 text-right">Reference</dt>
                    <dd class="col-sm-9">{{ $invoice->reference }}</dd>
                    <dt class="col-sm-3 text-right">Description</dt>
                    <dd class="col-sm-9">{{ $invoice->description }}</dd>
                    <dt class="col-sm-3 text-right">Labels</dt>
                    <dd class="col-sm-9">@include('laravel-crm::partials.labels',[
                            'labels' => $invoice->labels
                    ])</dd>
                    <dt class="col-sm-3 text-right">Owner</dt>
                    <dd class="col-sm-9">{{ $invoice->ownerUser->name ?? null }}</dd>
                </dl>

                <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.contact_person')) }}</h6>
                <hr />
                <p><span class="fa fa-user" aria-hidden="true"></span> @if($invoice->person)<a href="{{ route('laravel-crm.people.show',$invoice->person) }}">{{ $invoice->person->name }}</a>@endif </p>
                @isset($email)
                    <p><span class="fa fa-envelope" aria-hidden="true"></span> <a href="mailto:{{ $email->address }}">{{ $email->address }}</a> ({{ ucfirst($email->type) }})</p>
                @endisset
                @isset($phone)
                    <p><span class="fa fa-phone" aria-hidden="true"></span> <a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> ({{ ucfirst($phone->type) }})</p>
                @endisset
                <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.organization')) }}</h6>
                <hr />
                <p><span class="fa fa-building" aria-hidden="true"></span> @if($invoice->organisation)<a href="{{ route('laravel-crm.organisations.show',$invoice->organisation) }}">{{ $invoice->organisation->name }}</a>@endif</p>
                <p><span class="fa fa-map-marker" aria-hidden="true"></span> {{ ($organisation_address) ? \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($organisation_address) : null }} </p>
                @can('view crm products')
                <h6 class="text-uppercase mt-4 section-h6-title-table"><span>{{ ucfirst(__('laravel-crm::lang.invoice_items')) }} ({{ $invoice->invoiceProducts->count() }})</span></h6>
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($invoice->invoiceProducts()->whereNotNull('product_id')->get() as $invoiceProduct)
                        <tr>
                            <td>{{ $invoiceProduct->product->name }}</td>
                            <td>{{ money($invoiceProduct->price ?? null, $invoiceProduct->currency) }}</td>
                            <td>{{ $invoiceProduct->quantity }}</td>
                            <td>{{ money($invoiceProduct->amount ?? null, $invoiceProduct->currency) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td><strong>{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</strong></td>
                        <td>{{ money($invoice->subtotal, $invoice->currency) }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td><strong>{{ ucfirst(__('laravel-crm::lang.discount')) }}</strong></td>
                        <td>{{ money($invoice->discount, $invoice->currency) }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td><strong>{{ ucfirst(__('laravel-crm::lang.tax')) }}</strong></td>
                        <td>{{ money($invoice->tax, $invoice->currency) }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td><strong>{{ ucfirst(__('laravel-crm::lang.adjustment')) }}</strong></td>
                        <td>{{ money($invoice->adjustments, $invoice->currency) }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td><strong>{{ ucfirst(__('laravel-crm::lang.total')) }}</strong></td>
                        <td>{{ money($invoice->total, $invoice->currency) }}</td>
                    </tr>
                    </tfoot>
                </table>
                @endcan
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.activities', [
                    'model' => $invoice
                ])
            </div>
        </div>

    @endcomponent

@endcomponent
