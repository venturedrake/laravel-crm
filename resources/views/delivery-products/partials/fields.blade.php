<tr wire:key="select2-{{ $value }}" data-number="{{ $value }}" class="item-tr">
    <td class="pt-3 bind-select2" style="position: relative;">
        @include('laravel-crm::partials.form.hidden',[
          'name' => 'products['.$value.'][delivery_product_id]',
          'attributes' => [
              'wire:model' => 'delivery_product_id.'.$value,
          ]
        ])
        @include('laravel-crm::partials.form.hidden',[
           'name' => 'products['.$value.'][order_product_id]',
           'attributes' => [
               'wire:model' => 'order_product_id.'.$value,
           ]
        ])
        <span wire:ignore>
             @if($fromOrder)
                 
                @include('laravel-crm::partials.form.hidden',[
                    'name' => 'products['.$value.'][product_id]',
                    'attributes' => [
                       'wire:model' => 'product_id.'.$value,
                    ]
                ])
                
                @include('laravel-crm::partials.form.text',[
                   'name' => 'products['.$value.'][name]',
                   'label' => ucfirst(__('laravel-crm::lang.name')),
                   'attributes' => [
                       'wire:model' => 'name.'.$value,
                       'readonly' => 'readonly'
                   ]
               ])
                 
            @else
                @include('laravel-crm::partials.form.select',[
                    'name' => 'products['.$value.'][product_id]',
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
        @if(!isset($fromOrder))
        <span style="position: absolute;top:13%; right: 5px;">
            <button wire:click.prevent="remove({{ $value }})" type="button" class="btn btn-outline-danger btn-sm btn-close"><span class="fa fa-remove"></span></button>
        </span>
        @endif    
    </td>
</tr>
<tr data-number="{{ $value }}" class="item-tr">
    <td class="border-0 pt-0">
        @if($fromOrder)
            @include('laravel-crm::partials.form.select',[
                'name' => 'products['.$value.'][quantity]',
                'label' => ucfirst(__('laravel-crm::lang.quantity')),
                'options' => $this->order_quantities[$value],
                'value' => $this->quantity[$value] ?? null,
                'attributes' => [
                    'wire:model' => 'quantity.'.$value,
                    'data-value' => $value,
                ]
            ])
        @else    
            @include('laravel-crm::partials.form.text',[
               'name' => 'products['.$value.'][quantity]',
               'label' => ucfirst(__('laravel-crm::lang.quantity')),
               'type' => 'number',
               'attributes' => [
                   'wire:model' => 'quantity.'.$value,
                   'wire:change' => 'calculateAmounts'
               ]
           ])
       @endif     
    </td>
</tr>
