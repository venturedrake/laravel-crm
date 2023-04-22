@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ $quote->title }}
        @endslot

        @slot('actions')
            <span class="float-right">
                @include('laravel-crm::partials.return-button',[
                    'model' => $quote,
                    'route' => 'quotes'
                ]) |
                @if(! $quote->order)
                    @livewire('send-quote',[
                    'quote' => $quote
                    ])
                @endif
                @can('edit crm quotes')
                    @if(!$quote->accepted_at && !$quote->rejected_at)
                        <a href="{{  route('laravel-crm.quotes.accept',$quote) }}" class="btn btn-success btn-sm">{{ ucfirst(__('laravel-crm::lang.mark_as_accepted')) }}</a>
                        <a href="{{  route('laravel-crm.quotes.reject',$quote) }}" class="btn btn-danger btn-sm">{{ ucfirst(__('laravel-crm::lang.mark_as_rejected')) }}</a>
                    @elseif($quote->order)
                        <a href="{{  route('laravel-crm.orders.show',$quote->order) }}" class="btn btn-outline-secondary btn-sm">{{ ucfirst(__('laravel-crm::lang.ordered')) }}</a>
                    @elseif($quote->accepted_at)
                        <a href="{{  route('laravel-crm.quotes.unaccept',$quote) }}" class="btn btn-outline-secondary btn-sm">{{ ucfirst(__('laravel-crm::lang.unaccept')) }}</a>
                        <a href="{{  route('laravel-crm.quotes.create-order',$quote) }}" class="btn btn-success btn-sm">{{ ucfirst(__('laravel-crm::lang.create_order')) }}</a>
                    @endif
                @endcan
                @can('view crm quotes')
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('laravel-crm.quotes.download', $quote) }}">{{ ucfirst(__('laravel-crm::lang.download')) }}</a>
                @endcan    
                @include('laravel-crm::partials.navs.activities') |
                @can('edit crm quotes')
                    @if(! $quote->order && ! $quote->accepted_at)
                        <a href="{{  route('laravel-crm.quotes.edit',$quote) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                    @endif
                @endcan
                @can('delete crm quotes')
                <form action="{{ route('laravel-crm.quotes.destroy',$quote) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.quote') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
                @endcan
            </span>
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-body')

        <div class="row card-show card-fa-w30">
            <div class="col-sm-6 border-right">
                <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.details')) }}</h6>
                <hr />
                <dl class="row">
                    <dt class="col-sm-3 text-right">Reference</dt>
                    <dd class="col-sm-9">{{ $quote->reference }}</dd>
                    <dt class="col-sm-3 text-right">Issue Date</dt>
                    <dd class="col-sm-9">{{ ($quote->issue_at) ? $quote->issue_at->format($dateFormat) : null }}</dd>
                    <dt class="col-sm-3 text-right">Expiry Date</dt>
                    <dd class="col-sm-9">{{ ($quote->expire_at) ? $quote->expire_at->format($dateFormat) : null }}</dd>
                    <dt class="col-sm-3 text-right">Description</dt>
                    <dd class="col-sm-9">{{ $quote->description }}</dd>
                    <dt class="col-sm-3 text-right">Labels</dt>
                    <dd class="col-sm-9">@include('laravel-crm::partials.labels',[
                            'labels' => $quote->labels
                    ])</dd>
                    <dt class="col-sm-3 text-right">Owner</dt>
                    <dd class="col-sm-9">{{ $quote->ownerUser->name ?? null }}</dd>
                </dl>
                <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.client')) }}</h6>
                <hr />
                <p><span class="fa fa-address-card" aria-hidden="true"></span> @if($quote->client)<a href="{{ route('laravel-crm.clients.show',$quote->client) }}">{{ $quote->client->name }}</a>@endif </p>
                <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.organization')) }}</h6>
                <hr />
                <p><span class="fa fa-building" aria-hidden="true"></span> @if($quote->organisation)<a href="{{ route('laravel-crm.organisations.show',$quote->organisation) }}">{{ $quote->organisation->name }}</a>@endif</p>
                <p><span class="fa fa-map-marker" aria-hidden="true"></span> {{ ($organisation_address) ? \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($organisation_address) : null }} </p>
                <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.contact_person')) }}</h6>
                <hr />
                <p><span class="fa fa-user" aria-hidden="true"></span> @if($quote->person)<a href="{{ route('laravel-crm.people.show',$quote->person) }}">{{ $quote->person->name }}</a>@endif </p>
                @isset($email)
                    <p><span class="fa fa-envelope" aria-hidden="true"></span> <a href="mailto:{{ $email->address }}">{{ $email->address }}</a> ({{ ucfirst($email->type) }})</p>
                @endisset
                @isset($phone)
                    <p><span class="fa fa-phone" aria-hidden="true"></span> <a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> ({{ ucfirst($phone->type) }})</p>
                @endisset
                @can('view crm products')
                <h6 class="text-uppercase mt-4 section-h6-title-table"><span>{{ ucfirst(__('laravel-crm::lang.quote_items')) }} ({{ $quote->quoteProducts->count() }})</span></h6>
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
                    @foreach($quote->quoteProducts()->whereNotNull('product_id')->get() as $quoteProduct)
                        <tr>
                            <td>{{ $quoteProduct->product->name }}</td>
                            <td>{{ money($quoteProduct->price ?? null, $quoteProduct->currency) }}</td>
                            <td>{{ $quoteProduct->quantity }}</td>
                            <td>{{ money($quoteProduct->amount ?? null, $quoteProduct->currency) }}</td>
                        </tr>
                        @if($quoteProduct->comments)
                        <tr>
                            <td colspan="4" class="border-0 pt-0">
                                <strong>{{ ucfirst(__('laravel-crm::lang.comments')) }}</strong><br />
                                {{ $quoteProduct->comments }}
                            </td>
                        </tr>
                        @endif
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</strong></td>
                            <td>{{ money($quote->subtotal, $quote->currency) }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.discount')) }}</strong></td>
                            <td>{{ money($quote->discount, $quote->currency) }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.tax')) }}</strong></td>
                            <td>{{ money($quote->tax, $quote->currency) }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.adjustment')) }}</strong></td>
                            <td>{{ money($quote->adjustments, $quote->currency) }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.total')) }}</strong></td>
                            <td>{{ money($quote->total, $quote->currency) }}</td>
                        </tr>
                    </tfoot>
                </table>
                @endcan
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.activities', [
                    'model' => $quote
                ])
            </div>
        </div>

    @endcomponent

@endcomponent
