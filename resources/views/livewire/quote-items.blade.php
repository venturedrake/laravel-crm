<div>
    <h6 class="text-uppercase section-h6-title"><span class="fa fa-cart-arrow-down" aria-hidden="true"></span> {{ ucfirst(__('laravel-crm::lang.quote_items')) }} <span class="float-right"><a href="{{ (isset($quote)) ? url(route('laravel-crm.quote-products.create', $quote)) : url(route('laravel-crm.quote-products.create-product')) }}" class="btn btn-outline-secondary btn-sm btn-action-add-quote-product"><span class="fa fa-plus" aria-hidden="true"></span></a></span></h6>
    <hr />
    <script type="text/javascript">
        let products =  {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\products() !!}
    </script>
    <span id="quoteProducts">
        @if(isset($quote) && method_exists($quote,'quoteProducts'))
            @foreach($quote->quoteProducts as $quoteProduct)
                @include('laravel-crm::quote-products.partials.fields',[
                    'index' => $loop->index
                ])
            @endforeach
        @endif    
    </span>
    <hr />
    Sub total<br />
    Discount<br />
    Tax<br />
    Adjustment<br />
    Total
</div>