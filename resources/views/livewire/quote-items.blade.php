<div>
    <h6 class="text-uppercase section-h6-title"><span class="fa fa-cart-arrow-down" aria-hidden="true"></span> {{ ucfirst(__('laravel-crm::lang.quote_items')) }} <span class="float-right"><button class="btn btn-outline-secondary btn-sm" wire:click.prevent="add({{ $i }})"><span class="fa fa-plus" aria-hidden="true"></span></button></span></h6>
    <hr />
    <script type="text/javascript">
        let products =  {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\products() !!}
    </script>
    <span id="quoteProducts">
        @foreach($inputs as $key => $value)
            @include('laravel-crm::quote-products.partials.fields')
        @endforeach
    </span>
    <hr />
    <span id="quoteProductsTotals">
        <div class="row">
            <div class="col offset-8 text-right">
                @include('laravel-crm::partials.form.text',[
                  'name' => 'sub_total',
                   'label' => ucfirst(__('laravel-crm::lang.sub_total')),
                   'type' => 'number',
                   'prepend' => ucfirst(__('laravel-crm::lang.sub_total')),
                   'attributes' => [
                       'wire:model' => 'sub_total',
                       'step' => .01,
                       'readonly' => 'readonly'
                   ]
                ])
            </div>
        </div>
        <div class="row">
            <div class="col offset-8 text-right">
                @include('laravel-crm::partials.form.text',[
                  'name' => 'discount',
                  'label' => ucfirst(__('laravel-crm::lang.discount')),
                   'type' => 'number',
                   'prepend' => ucfirst(__('laravel-crm::lang.discount')),
                   'attributes' => [
                       'wire:model' => 'discount',
                       'step' => .01,
                       'readonly' => 'readonly'
                   ]
                ])
            </div>
        </div>
        <div class="row">
            <div class="col offset-8 text-right">
                @include('laravel-crm::partials.form.text',[
                  'name' => 'tax',
                  'label' => ucfirst(__('laravel-crm::lang.tax')),
                   'type' => 'number',
                   'prepend' => ucfirst(__('laravel-crm::lang.tax')),
                   'attributes' => [
                       'wire:model' => 'tax',
                       'step' => .01,
                       'readonly' => 'readonly'
                   ]
                ])
            </div>
        </div>
        <div class="row">
            <div class="col offset-8 text-right">
                @include('laravel-crm::partials.form.text',[
                  'name' => 'adjustment',
                  'label' => ucfirst(__('laravel-crm::lang.adjustment')),
                   'type' => 'number',
                   'prepend' => ucfirst(__('laravel-crm::lang.adjustment')),
                   'attributes' => [
                       'wire:model' => 'adjustment',
                       'step' => .01,
                       'readonly' => 'readonly'
                   ]
                ])
            </div>
        </div>
        <div class="row">
            <div class="col offset-8 text-right">
                @include('laravel-crm::partials.form.text',[
                  'name' => 'total',
                  'label' => ucfirst(__('laravel-crm::lang.total')),
                   'type' => 'number',
                   'prepend' => ucfirst(__('laravel-crm::lang.total')),
                   'attributes' => [
                       'wire:model' => 'total',
                       'step' => .01,
                       'readonly' => 'readonly'
                   ]
                ])
            </div>
        </div>
    </span>

    @push('livewire-js')
        <script>
            $(document).ready(function () {
                $(document).delegate("input[name^='products']", "focus", function() {
                    var number = $(this).attr('value')
                    $(this).autocomplete({
                        source: products,
                        onSelectItem: function(item, element){
                            @this.set('product_id.' + number,item.value);
                            @this.set('name.' + number,item.label);
                            Livewire.emit('loadItemDefault', number)
                        },
                        highlightClass: 'text-danger',
                        treshold: 2,
                    });
                })
            });
        </script>
    @endpush
</div>