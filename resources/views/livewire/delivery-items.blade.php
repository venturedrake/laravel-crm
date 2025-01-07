<div>
    <h6 class="text-uppercase section-h6-title"><span class="fa fa-cart-arrow-down" aria-hidden="true"></span> {{ ucfirst(__('laravel-crm::lang.delivery_items')) }} @if(!isset($fromOrder))<span class="float-right"><button class="btn btn-outline-secondary btn-sm" wire:click.prevent="add({{ $i }})"><span class="fa fa-plus" aria-hidden="true"></span></button></span>@endif</h6>
    <hr class="mb-0" />
    <script type="text/javascript">
        let products =  {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\productsSelect2() !!}
    </script>
    <span id="deliveryProducts">
        <div class="table-responsive">
            <table class="table table-sm table-items">
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
                    @include('laravel-crm::delivery-products.partials.fields')
                @endforeach
                </tbody>
            </table>
        </div>
    </span>

    @push('livewire-js')
        <script>
            $(document).ready(function () {
                /*$(document).delegate("input[name^='products']", "focus", function() {
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
                })*/

                window.addEventListener('addedItem', event => {
                    $("tr[data-number='" + event.detail.id + "'] td.bind-select2 select[name^='products']").select2({
                        data: products,
                    }).select2('open')
                        .on('change', function (e) {
                            @this.set('product_id.' + $(this).data('value'), $(this).val());
                            @this.set('name.' + $(this).data('value'), $(this).find("option:selected").text());
                            Livewire.emit('loadItemDefault', $(this).data('value'))
                        });
                });

                /*window.addEventListener('removedItem', event => {
                     $("tr[data-number='" + event.detail.id + "']").remove()
                });*/

                $("td.bind-select2 select[name^='products']").on('change', function (e) {
                    @this.set('product_id.' + $(this).data('value'), $(this).val());
                    @this.set('name.' + $(this).data('value'), $(this).find("option:selected").text());
                    Livewire.emit('loadItemDefault', $(this).data('value'))
                });
            });
        </script>
    @endpush
</div>
