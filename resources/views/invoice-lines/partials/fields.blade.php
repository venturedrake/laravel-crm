<tr wire:key="select2-{{ $value }}" data-number="{{ $value }}" class="item-tr">
    <td colspan="5" class="pt-3" style="position: relative;">
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
                'label' => ucfirst(__('laravel-crm::lang.name')),
                'options' => [
                    $this->product_id[$value] ?? null => $this->name[$value] ?? null,
                ],
                'value' => $this->product_id[$value] ?? null,      
                'attributes' => [
                    'wire:model' => 'product_id.'.$value,
                    'data-value' => $value
                ]
            ])
        </span>
        <span style="position: absolute;top:13%; right: 5px;">
            <button wire:click.prevent="remove({{ $value }})" type="button" class="btn btn-outline-danger btn-sm btn-close"><span class="fa fa-remove"></span></button>
        </span>
    </td>
</tr>
<tr data-number="{{ $value }}" class="item-tr">
    <td colspan="3" class="border-0 pt-0">
        @include('laravel-crm::partials.form.text',[
          'name' => 'invoiceLines['.$value.'][price]',
           'label' => ucfirst(__('laravel-crm::lang.price')),
           'type' => 'number',
           'prepend' => '<span class="fa fa-dollar" aria-hidden="true"></span>',
           'attributes' => [
               'wire:model' => 'price.'.$value,
               'wire:change' => 'calculateAmounts',
               'step' => .01
           ]
       ])
    </td>
    <td class="border-0 pt-0">
        @include('laravel-crm::partials.form.text',[
           'name' => 'invoiceLines['.$value.'][quantity]',
           'label' => ucfirst(__('laravel-crm::lang.quantity')),
           'type' => 'number',
           'attributes' => [
               'wire:model' => 'quantity.'.$value,
               'wire:change' => 'calculateAmounts'
           ]
       ])
    </td>
    <td class="border-0 pt-0">
        @include('laravel-crm::partials.form.text',[
         'name' => 'invoiceLines['.$value.'][amount]',
          'label' => ucfirst(__('laravel-crm::lang.amount')),
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
<tr data-number="{{ $value }}" class="item-tr">
    <td colspan="5" class="border-0 pt-0 pb-4">
        @include('laravel-crm::partials.form.text',[
           'name' => 'invoiceLines['.$value.'][comments]',
           'label' => ucfirst(__('laravel-crm::lang.comments')),
           'attributes' => [
               'wire:model' => 'comments.'.$value,
           ]
       ])
    </td>
</tr>
