<tr wire:key="select2-{{ $value }}" data-number="{{ $value }}" class="item-tr">
    <td colspan="5" class="pt-3 bind-select2" style="position: relative;">
        @include('laravel-crm::partials.form.hidden',[
          'name' => 'invoiceLines['.$value.'][order_product_id]',
          'attributes' => [
              'wire:model' => 'order_product_id.'.$value,
          ]
        ])
        
        @include('laravel-crm::partials.form.hidden',[
           'name' => 'invoiceLines['.$value.'][invoice_line_id]',
           'attributes' => [
               'wire:model' => 'invoice_line_id.'.$value,
           ]
        ])
        <span wire:ignore>
            @if($fromOrder)

                @include('laravel-crm::partials.form.hidden',[
                    'name' => 'invoiceLines['.$value.'][product_id]',
                    'attributes' => [
                       'wire:model' => 'product_id.'.$value,
                    ]
                ])

                @include('laravel-crm::partials.form.text',[
                   'name' => 'invoiceLines['.$value.'][name]',
                   'label' => ucfirst(__('laravel-crm::lang.name')),
                   'attributes' => [
                       'wire:model' => 'name.'.$value,
                       'readonly' => 'readonly'
                   ]
               ])

            @else
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
            @endif
        </span>
        @if(!$fromOrder)
        <span style="position: absolute;top:13%; right: 5px;">
            <button wire:click.prevent="remove({{ $value }})" type="button" class="btn btn-outline-danger btn-sm btn-close"><span class="fa fa-remove"></span></button>
        </span>
        @endif    
    </td>
</tr>
<tr data-number="{{ $value }}" class="item-tr">
    <td colspan="3" class="border-0 pt-0">
        @if($fromOrder)
            @include('laravel-crm::partials.form.text',[
                  'name' => 'invoiceLines['.$value.'][price]',
                   'label' => ucfirst(__('laravel-crm::lang.price')),
                   'type' => 'number',
                   'prepend' => '<span class="fa fa-dollar" aria-hidden="true"></span>',
                   'attributes' => [
                       'wire:model' => 'price.'.$value,
                       'wire:change' => 'calculateAmounts',
                       'step' => .01,
                        'readonly' => 'readonly'
                   ]
                ])
        @else
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
        @endif
    </td>
    <td class="border-0 pt-0">
        @if($fromOrder)
            @include('laravel-crm::partials.form.select',[
               'name' => 'invoiceLines['.$value.'][quantity]',
               'label' => ucfirst(__('laravel-crm::lang.quantity')),
               'options' => $this->order_quantities[$value],
               'value' => $this->quantity[$value] ?? null,
               'attributes' => [
                   'wire:model' => 'quantity.'.$value,
                   'data-value' => $value,
                   'wire:change' => 'calculateAmounts'
               ]
           ])
        @else
            @include('laravel-crm::partials.form.text',[
                   'name' => 'invoiceLines['.$value.'][quantity]',
                   'label' => ucfirst(__('laravel-crm::lang.quantity')),
                   'type' => 'number',
                   'attributes' => [
                       'wire:model' => 'quantity.'.$value,
                       'wire:change' => 'calculateAmounts'
                   ]
                ])
        @endif
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
        @if($fromOrder)
            @include('laravel-crm::partials.form.text',[
               'name' => 'invoiceLines['.$value.'][comments]',
               'label' => ucfirst(__('laravel-crm::lang.comments')),
               'attributes' => [
                   'wire:model' => 'comments.'.$value,
                    'readonly' => 'readonly'
               ]
           ])
        @else    
            @include('laravel-crm::partials.form.text',[
               'name' => 'invoiceLines['.$value.'][comments]',
               'label' => ucfirst(__('laravel-crm::lang.comments')),
               'attributes' => [
                   'wire:model' => 'comments.'.$value,
               ]
           ])
       @endif     
    </td>
</tr>
