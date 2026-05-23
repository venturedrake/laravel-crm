@extends('laravel-crm::layouts.portal')

@section('content')

    <header>
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm fixed-top">
            <div class="container-fluid">
                <h1 class="navbar-brand mb-0" href="#">
                    {{ money($purchaseOrder->total, $purchaseOrder->currency) }} <small>{{ $purchaseOrder->currency }}</small>
                </h1>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item">
                            <form action="{{ url()->current() }}?signature={{ request()->input('signature') }}&expires={{ request()->input('expires') }}" method="POST" class="form-check-inline mr-0">
                                {{ csrf_field() }}
                                <input type="hidden" name="action" value="download" />
                                <button class="btn btn-outline-secondary" type="submit"><span class="fa fa-download" aria-hidden="true"></span> {{ ucfirst(__('laravel-crm::lang.download')) }}</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main role="main" class="flex-shrink-0">
        <div class="container">
            <div class="row card shadow-sm mb-4">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col px-5 py-4">
                            <h1 class="card-title pricing-card-title py-4 m-0">{{ ucfirst(__('laravel-crm::lang.purchase_order')) }}</h1>
                        </div>
                        <div class="col px-5 py-4 text-right">
                            @if($logo)
                                <img src="{{ asset('storage/'.$logo) }}" height="80" />
                            @endif
                        </div>
                    </div>
                    <hr class="m-0" />
                    <div class="row">
                        <div class="col px-5 py-4">
                            <div class="row py-1">
                                <div class="col-3">
                                    <strong>{{ ucfirst(__('laravel-crm::lang.supplier')) }}</strong>
                                </div>
                                <div class="col">
                                    @if($purchaseOrder->organization)
                                        {{ $purchaseOrder->organization->name }}<br />
                                    @endif
                                    @isset($purchaseOrder->person)
                                        {{ $purchaseOrder->person->name }}<br />
                                    @endisset
                                    @if(isset($organization_address))
                                        @if($organization_address->line1)
                                            {{ $organization_address->line1 }}<br />
                                        @endif
                                        @if($organization_address->line2)
                                            {{ $organization_address->line2 }}<br />
                                        @endif
                                        @if($organization_address->line3)
                                            {{ $organization_address->line3 }}<br />
                                        @endif
                                        @if($organization_address->city || $organization_address->state || $organization_address->postcode)
                                            {{ $organization_address->city }} {{ $organization_address->state }} {{ $organization_address->postcode }}<br />
                                        @endif
                                        {{ $organization_address->country }}
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
                                </div>
                            </div>
                            @if($purchaseOrder->purchase_order_id)
                            <div class="row py-1">
                                <div class="col-3">
                                    <strong>{{ ucfirst(__('laravel-crm::lang.purchase_order_number')) }}</strong>
                                </div>
                                <div class="col">
                                    {{ $purchaseOrder->xeroPurchaseOrder->number ?? $purchaseOrder->purchase_order_id }}
                                </div>
                            </div>
                            @endif
                            @if($purchaseOrder->issue_date)
                            <div class="row py-1">
                                <div class="col-3">
                                    <strong>{{ ucfirst(__('laravel-crm::lang.purchase_order_date')) }}</strong>
                                </div>
                                <div class="col">
                                    {{ $purchaseOrder->issue_date->format($dateFormat) }}
                                </div>
                            </div>
                            @endif
                            @if($purchaseOrder->delivery_date)
                            <div class="row py-1">
                                <div class="col-3">
                                    <strong>{{ ucfirst(__('laravel-crm::lang.delivery_date')) }}</strong>
                                </div>
                                <div class="col">
                                    {{ $purchaseOrder->delivery_date->format($dateFormat) }}
                                </div>
                            </div>
                            @endif
                            @if($purchaseOrder->reference || ($purchaseOrder->xeroPurchaseOrder && $purchaseOrder->xeroPurchaseOrder->reference))
                            <div class="row py-1">
                                <div class="col-3">
                                    <strong>{{ ucfirst(__('laravel-crm::lang.reference')) }}</strong>
                                </div>
                                <div class="col">
                                    {{ $purchaseOrder->xeroPurchaseOrder->reference ?? $purchaseOrder->reference }}
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="col px-5 py-4">
                            <div class="row py-1">
                                <div class="col-3">
                                    <strong>{{ ucfirst(__('laravel-crm::lang.delivery_details')) }}</strong>
                                </div>
                                <div class="col">
                                    {{ $fromName }}<br />
                                    @if($purchaseOrder->delivery_type == 'pickup')
                                        {{ strtoupper(__('laravel-crm::lang.pickup')) }}
                                    @elseif($purchaseOrder->address)
                                        {{ $purchaseOrder->address->line1 }}<br />
                                        @if($purchaseOrder->address->line2)
                                            {{ $purchaseOrder->address->line2 }}<br />
                                        @endif
                                        @if($purchaseOrder->address->line3)
                                            {{ $purchaseOrder->address->line3 }}<br />
                                        @endif
                                        {{ $purchaseOrder->address->city }} {{ $purchaseOrder->address->state }} {{ $purchaseOrder->address->postcode }}<br />
                                        {{ $purchaseOrder->address->country }}
                                        @if($purchaseOrder->address->contact)
                                            <br /><strong>{{ ucfirst(__('laravel-crm::lang.delivery_contact')) }}</strong> {{ $purchaseOrder->address->contact }}
                                        @endif
                                        @if($purchaseOrder->address->phone)
                                            <br /><strong>{{ ucfirst(__('laravel-crm::lang.delivery_phone')) }}</strong> {{ $purchaseOrder->address->phone }}
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="mb-5" />
                    <div class="row py-1">
                        <div class="col px-5 py-1">
                            <table class="table table-hover mb-0">
                                <thead>
                                <tr>
                                    <th scope="col">{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                                    <th scope="col">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                                    <th scope="col">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
                                    <th scope="col">{{ $taxName }}</th>
                                    <th scope="col">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
                                    <th scope="col">{{ ucfirst(__('laravel-crm::lang.comments')) }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($purchaseOrder->purchaseOrderLines()->whereNotNull('product_id')->orderBy('order', 'asc')->orderBy('created_at', 'asc')->get() as $purchaseOrderLine)
                                    <tr>
                                        <td>{{ $purchaseOrderLine->product->name ?? null }}</td>
                                        <td>{{ money($purchaseOrderLine->price ?? null, $purchaseOrderLine->currency) }}</td>
                                        <td>{{ $purchaseOrderLine->quantity }}</td>
                                        <td>{{ money($purchaseOrderLine->tax_amount ?? null, $purchaseOrderLine->currency) }}</td>
                                        <td>{{ money($purchaseOrderLine->amount ?? null, $purchaseOrderLine->currency) }}</td>
                                        <td>{{ $purchaseOrderLine->comments }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td><strong>{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</strong></td>
                                    <td>{{ money($purchaseOrder->subtotal, $purchaseOrder->currency) }}</td>
                                    <td></td>
                                </tr>
                                @if($purchaseOrder->discount > 0)
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td><strong>{{ ucfirst(__('laravel-crm::lang.discount')) }}</strong></td>
                                    <td>{{ money($purchaseOrder->discount, $purchaseOrder->currency) }}</td>
                                    <td></td>
                                </tr>
                                @endif
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td><strong>{{ $taxName }}</strong></td>
                                    <td>{{ money($purchaseOrder->tax, $purchaseOrder->currency) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td><strong>{{ ucfirst(__('laravel-crm::lang.total')) }}</strong></td>
                                    <td>{{ money($purchaseOrder->total, $purchaseOrder->currency) }}</td>
                                    <td></td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <hr class="m-0" />
                    @if($purchaseOrder->terms)
                        <div class="row py-1">
                            <div class="col px-5 py-4">
                                <h5>{{ ucfirst(__('laravel-crm::lang.terms')) }}</h5>
                                {!! nl2br($purchaseOrder->terms) !!}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

@endsection

