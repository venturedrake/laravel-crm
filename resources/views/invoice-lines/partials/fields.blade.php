<tr data-number="{{ $value }}">
    <td>
        @include('laravel-crm::partials.form.hidden',[
           'name' => 'invoiceLines['.$value.'][invoice_line_id]',
           'attributes' => [
               'wire:model' => 'invoice_line_id.'.$value,
           ]
        ])
        {{--<span class="autocomplete autocomplete-product-name">
            @include('laravel-crm::partials.form.hidden',[
                'name' => 'invoiceLines['.$value.'][product_id]',
                'attributes' => [
                    'wire:model' => 'product_id.'.$value,
                ]
            ])
            <span wire:ignore>
                @include('laravel-crm::partials.form.text',[
                    'name' => 'invoiceLines['.$value.'][name]',
                    /*'label' => ucfirst(__('laravel-crm::lang.name')),*/
                    'attributes' => [
                        'wire:model' => 'name.'.$value,
                        'autocomplete' => \Illuminate\Support\Str::random(),
                    ]
                ])
            </span>
        </span>--}}
        <span wire:ignore>
             @include('laravel-crm::partials.form.select',[
                'name' => 'invoiceLines['.$value.'][product_id]',
                /*'label' => ucfirst(__('laravel-crm::lang.name')),*/
                'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Product::all(), true),      
                'attributes' => [
                    'wire:model' => 'product_id.'.$value,
                    'data-value' => $value
                ]
            ])
        </span>
    </td>
    <td>
        @include('laravel-crm::partials.form.text',[
          'name' => 'invoiceLines['.$value.'][price]',
          /* 'label' => ucfirst(__('laravel-crm::lang.price')),*/
           'type' => 'number',
           'prepend' => '<span class="fa fa-dollar" aria-hidden="true"></span>',
           'attributes' => [
               'wire:model' => 'price.'.$value,
               'wire:change' => 'calculateAmounts',
               'step' => .01
           ]
       ])
    </td>
    <td>
        @include('laravel-crm::partials.form.text',[
           'name' => 'invoiceLines['.$value.'][quantity]',
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
         'name' => 'invoiceLines['.$value.'][amount]',
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
    <td>
        <button wire:click.prevent="remove({{ $value }})" type="button" class="btn btn-outline-danger btn-sm"><span class="fa fa-remove"></span></button>
    </td>
</tr>
