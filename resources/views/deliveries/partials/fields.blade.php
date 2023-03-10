<div class="row">
    <div class="col-sm-5 border-right">
        @livewire('address-edit', [
            'addresses' => $addresses ?? null,
            'old' => old('addresses'),
            'model' => 'delivery'
        ])
    </div>
    <div class="col-sm-7">
     {{--   
        @livewire('delivery-items',[
            'order' => $order ?? null,
            'products' => $order->orderProducts ?? null,
            'old' => old('products')
        ])--}}
    </div>
</div>
