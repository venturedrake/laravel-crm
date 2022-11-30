<tr>
    <td>
        @include('laravel-crm::partials.form.hidden',[
           'name' => 'products['.$value.'][quote_product_id]',
           'attributes' => [
               'wire:model' => 'quote_product_id.'.$value,
           ]
        ])
        <span class="autocomplete autocomplete-product-name">
            @include('laravel-crm::partials.form.hidden',[
                'name' => 'products['.$value.'][product_id]',
                'attributes' => [
                    'wire:model' => 'product_id.'.$value,
                ]
            ])
            <span wire:ignore>
                @include('laravel-crm::partials.form.text',[
                    'name' => 'products['.$value.'][name]',
                    /*'label' => ucfirst(__('laravel-crm::lang.name')),*/
                    'attributes' => [
                        'wire:model' => 'name.'.$value,
                        'autocomplete' => \Illuminate\Support\Str::random(),
                    ]
                ])
            </span>
        </span>
    </td>
    <td>
        @include('laravel-crm::partials.form.text',[
          'name' => 'products['.$value.'][unit_price]',
          /* 'label' => ucfirst(__('laravel-crm::lang.price')),*/
           'type' => 'number',
           'prepend' => '<span class="fa fa-dollar" aria-hidden="true"></span>',
           'attributes' => [
               'wire:model' => 'unit_price.'.$value,
               'wire:change' => 'calculateAmounts',
               'step' => .01
           ]
       ])
    </td>
    <td>
        @include('laravel-crm::partials.form.text',[
           'name' => 'products['.$value.'][quantity]',
          /* 'label' => ucfirst(__('laravel-crm::lang.quantity')),*/
           'type' => 'number',
           'attributes' => [
               'wire:model' => 'quantity.'.$value,
               'wire:change' => 'calculateAmounts'
           ]
       ])
    </td>
    <td>
        @include('laravel-crm::partials.form.text',[
         'name' => 'products['.$value.'][amount]',
         /* 'label' => ucfirst(__('laravel-crm::lang.amount')),*/
          'type' => 'number',
          'prepend' => '<span class="fa fa-dollar" aria-hidden="true"></span>',
          'attributes' => [
              'wire:model' => 'amount.'.$value,
              'step' => .01,
              'readonly' => 'readonly'
          ]
      ])
    </td>
</tr>