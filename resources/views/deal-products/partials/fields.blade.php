<div class="row">
    <div class="col-6">
        <span class="autocomplete autocomplete-product-name" data-index="{{ $index }}">
            @include('laravel-crm::partials.form.hidden',[
               'name' => 'item_product_id['.$index.']',
               'value' => old('item_product_id.'.$index, $dealProduct->product->id ?? null),
            ])
        @include('laravel-crm::partials.form.text',[
                       'name' => 'item_name['.$index.']',
                       'label' => 'Item',
                       'value' => old('item_name.'.$index, $dealProduct->product->name ?? null),
                       'attributes' => [
                          'autocomplete' => \Illuminate\Support\Str::random(),
                  
                       ]
                   ])
        </span>
    </div>
    <div class="col">
        @include('laravel-crm::partials.form.text',[
            'name' => 'item__price['.$index.']',
            'label' => 'Price',
            'value' => old('item_price.'.$index, $dealProduct->unit_price ?? null) 
        ])
    </div>
    <div class="col">
        @include('laravel-crm::partials.form.text',[
           'name' => 'item_quantity['.$index.']',
           'label' => 'Quantity',
           'value' => old('item_quantity.'.$index, $dealProduct->quantity ?? null)
       ])
    </div>
    <div class="col">
        @include('laravel-crm::partials.form.text',[
            'name' => 'item_amount['.$index.']',
            'label' => 'Amount',
            'value' => old('item_amount.'.$index, $dealProduct->amount ?? null) ,
            'attributes' => [
                'readonly' => 'readonly'
            ]
        ])
    </div>
</div>