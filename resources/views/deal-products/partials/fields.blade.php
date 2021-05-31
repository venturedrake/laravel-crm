<div class="row deal-product-row">
    <div class="col-6">
        @include('laravel-crm::partials.form.hidden',[
              'name' => 'item_deal_product_id['.$index.']',
              'value' => old('item_deal_product_id.'.$index, $dealProduct->id ?? null),
           ])
        <span class="autocomplete autocomplete-product-name" data-index="{{ $index }}">
            @include('laravel-crm::partials.form.hidden',[
               'name' => 'item_product_id['.$index.']',
               'value' => old('item_product_id.'.$index, $dealProduct->product->id ?? null),
            ])
        @include('laravel-crm::partials.form.text',[
                       'name' => 'item_name['.$index.']',
                       'label' => ucfirst(__('laravel-crm::lang.item')),
                       'value' => old('item_name.'.$index, $dealProduct->product->name ?? null),
                       'attributes' => [
                          'autocomplete' => \Illuminate\Support\Str::random(),
                  
                       ]
                   ])
        </span>
    </div>
    <div class="col">
        @include('laravel-crm::partials.form.text',[
            'name' => 'item_price['.$index.']',
            'label' => ucfirst(__('laravel-crm::lang.price')),
            'type' => 'number',
            'value' => old('item_price.'.$index, ((isset($dealProduct->price)) ? $dealProduct->price / 100 : null) ?? null) ,
            'attributes' => [
                'step' => .01
            ]
        ])
    </div>
    <div class="col">
        @include('laravel-crm::partials.form.text',[
           'name' => 'item_quantity['.$index.']',
           'label' => ucfirst(__('laravel-crm::lang.quantity')),
           'type' => 'number',
           'value' => old('item_quantity.'.$index, $dealProduct->quantity ?? 1)
       ])
    </div>
    <div class="col">
        @include('laravel-crm::partials.form.text',[
            'name' => 'item_amount['.$index.']',
            'label' => ucfirst(__('laravel-crm::lang.amount')),
            'type' => 'number',
            'value' => old('item_amount.'.$index, ((isset($dealProduct->amount)) ? $dealProduct->amount / 100 : null) ?? null) ,
            'attributes' => [
                  'step' => .01,
                  'readonly' => 'readonly'
            ]
        ])
    </div>
</div>