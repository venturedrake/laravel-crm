<div>
    <h6 class="text-uppercase section-h6-title"><span class="fa fa-cart-arrow-down" aria-hidden="true"></span> {{ ucfirst(__('laravel-crm::lang.invoice_lines')) }} <span class="float-right"><button class="btn btn-outline-secondary btn-sm" wire:click.prevent="add({{ $i }})"><span class="fa fa-plus" aria-hidden="true"></span></button></span></h6>
    <hr class="mb-0" />
    <script type="text/javascript">
        let products =  {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\productsSelect2() !!}
    </script>
    <span id="invoiceLines">
        <div class="table-responsive">
            <table class="table table-sm table-items ">
                {{--<thead>
                    <tr>
                        <th scope="col" class="border-0">{{ ucfirst(__('laravel-crm::lang.name')) }}</th>
                        <th scope="col" class="col-3 border-0">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                        <th scope="col" class="col-2 border-0">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
                        <th scope="col" class="col-3 border-0">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
                    </tr>
                </thead>--}}
                <tbody>
                @foreach($inputs as $key => $value)
                    @include('laravel-crm::invoice-lines.partials.fields')
                @endforeach
                </tbody>
                <tfoot id="invoiceLinesTotals" class="tfoot">
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="text-right align-middle">
                            <button class="btn btn-outline-secondary btn-sm" wire:click.prevent="add({{ $i }})"><span class="fa fa-plus" aria-hidden="true"></span></button>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="text-right align-middle">{{  ucfirst(__('laravel-crm::lang.sub_total')) }}</td>
                        <td>
                            @include('laravel-crm::partials.form.text',[
                              'name' => 'sub_total',
                               'label' => ucfirst(__('laravel-crm::lang.sub_total')),
                               'type' => 'number',
                               'prepend' => '<span class="fa fa-dollar" aria-hidden="true"></span>',
                               'attributes' => [
                                   'wire:model' => 'sub_total',
                                   'step' => .01,
                                   'readonly' => 'readonly'
                               ]
                            ])
                        </td>
                    </tr>
                 
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-right align-middle">{{  ucfirst(__('laravel-crm::lang.tax')) }}</td>
                    <td>
                     @include('laravel-crm::partials.form.text',[
                      'name' => 'tax',
                      'label' => ucfirst(__('laravel-crm::lang.tax')),
                       'type' => 'number',
                       'prepend' => '<span class="fa fa-dollar" aria-hidden="true"></span>',
                       'attributes' => [
                           'wire:model' => 'tax',
                           'step' => .01,
                           'readonly' => 'readonly'
                       ]
                    ])
                    </td>
                  </tr>
                 <tr>
                    <td></td>
                    <td></td>
                     <td></td>
                    <td class="text-right align-middle">{{  ucfirst(__('laravel-crm::lang.total')) }}</td>
                    <td>
                   @include('laravel-crm::partials.form.text',[
                  'name' => 'total',
                  'label' => ucfirst(__('laravel-crm::lang.total')),
                   'type' => 'number',
                    'prepend' => '<span class="fa fa-dollar" aria-hidden="true"></span>',
                   'attributes' => [
                       'wire:model' => 'total',
                       'step' => .01,
                       'readonly' => 'readonly'
                   ]
                ])
                    </td>
                  </tr>
                </tfoot>
            </table>
        </div>
    </span>

    @push('livewire-js')
        <script>
            $(document).ready(function () {
                window.addEventListener('addedItem', event => {
                    if($('meta[name=dynamic_products]').length > 0){
                        var tags = JSON.parse($('meta[name=dynamic_products]').attr('content'));
                    }else{
                        var tags = true;
                    }
                    
                    $("tr[data-number='" + event.detail.id + "'] td.bind-select2 select[name^='invoiceLines']").select2({
                        data: products,
                        tags: tags
                    }).select2('open')
                        .on('change', function (e) {
                            @this.set('product_id.' + $(this).data('value'), $(this).val());
                            @this.set('name.' + $(this).data('value'), $(this).find("option:selected").text());
                            Livewire.emit('loadInvoiceLineDefault', $(this).data('value'))
                        });
                });

                $("td.bind-select2 select[name^='invoiceLines']").on('change', function (e) {
                    @this.set('product_id.' + $(this).data('value'), $(this).val());
                    @this.set('name.' + $(this).data('value'), $(this).find("option:selected").text());
                    Livewire.emit('loadInvoiceLineDefault', $(this).data('value'))
                });
            });
        </script>
    @endpush
</div>
