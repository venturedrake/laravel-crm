<div class="crm-content">
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$this->orders" :link="route('laravel-crm.orders.show', ['order' => '[id]'])" class="whitespace-nowrap">
            @scope('cell_labels', $order)
                @foreach($order->labels as $label)
                    <x-mary-badge :value="$label->name" class="text-white" :style="'border-color: #'.$label->hex.'; background-color: #'.$label->hex" />
                @endforeach
            @endscope
            @scope('cell_pipeline_stage', $order)
                @if($order->pipelineStage)
                    <x-mary-badge :value="$order->pipelineStage->name" class="badge badge-neutral text-white" />
                @endif
            @endscope
            @scope('cell_quote', $order)
                @if($order->quote)
                    <a href="{{ route('laravel-crm.quotes.show', $order->quote) }}" class="link link-hover link-primary">{{ $order->quote->quote_id }}</a>
                @endif
            @endscope
            @scope('cell_subtotal', $order)
                @php
                    (! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\subTotal($order)) ? $subTotalError = true : $subTotalError = false;
                @endphp
                @if($subTotalError)
                    <x-mary-popover>
                        <x-slot:trigger>
                            <span class="text-red-600">{{ money($order->subtotal, $order->currency) }}</span>
                        </x-slot:trigger>
                        <x-slot:content class="border border-danger text-red-600">
                            Error with sub total
                        </x-slot:content>
                    </x-mary-popover>
                @else
                    {{ money($order->subtotal, $order->currency) }}
                @endif
            @endscope
            @scope('cell_tax', $order)
                @php
                    (! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\tax($order)) ? $taxError = true : $taxError = false;
                @endphp
                @if($taxError)
                    <x-mary-popover>
                        <x-slot:trigger>
                            <span class="text-red-600">{{ money($order->tax, $order->currency) }}</span>
                        </x-slot:trigger>
                        <x-slot:content class="border border-danger text-red-600">
                            Error with tax
                        </x-slot:content>
                    </x-mary-popover>
                @else
                    {{ money($order->tax, $order->currency) }}
                @endif
            @endscope
            @scope('cell_total', $order)
                @php
                    (! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\total($order)) ? $totalError = true : $totalError = false;
                @endphp
                @if($totalError)
                    <x-mary-popover>
                        <x-slot:trigger>
                            <span class="text-red-600">{{ money($order->total, $order->currency) }}</span>
                        </x-slot:trigger>
                        <x-slot:content class="border border-danger text-red-600">
                            Error with total
                        </x-slot:content>
                    </x-mary-popover>
                @else
                    {{ money($order->total, $order->currency) }}
                @endif
            @endscope
            @scope('actions', $order)
                @php
                    (! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\subTotal($order)) ? $subTotalError = true : $subTotalError = false;
                    (! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\tax($order)) ? $taxError = true : $taxError = false;
                    (! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\total($order)) ? $totalError = true : $totalError = false;
                @endphp
                <div class="flex gap-1 justify-end">
                    @can('edit crm orders')
                        @if($subTotalError || $taxError || $totalError)
                            <x-mary-button link="{{ url(route('laravel-crm.orders.edit', $order)) }}" class="btn-sm btn-warning" label="{{ ucfirst(__('laravel-crm::lang.error_check_amounts')) }}" />
                        @else
                            @if(! $order->invoiceComplete())
                                @hasinvoicesenabled
                                    <x-mary-button link="{{ route('laravel-crm.invoices.create',['model' => 'order', 'id' => $order->id]) }}" class="btn-sm btn-success text-white" label="{{ ucfirst(__('laravel-crm::lang.invoice')) }}" />
                                @endhasinvoicesenabled
                            @endif
                            @if(! $order->deliveryComplete())
                                @hasdeliveriesenabled
                                    <x-mary-button link="{{ route('laravel-crm.deliveries.create',['model' => 'order', 'id' => $order->id]) }}" class="btn-sm btn-success text-white" label="{{ ucfirst(__('laravel-crm::lang.delivery')) }}" />
                                @endhasdeliveriesenabled
                            @endif
                            @if(! $order->deliveryComplete())
                                @haspurchaseordersenabled
                                    <x-mary-button link="{{ route('laravel-crm.purchase-orders.create',['model' => 'order', 'id' => $order->id]) }}" class="btn-sm btn-success text-white" label="{{ ucfirst(__('laravel-crm::lang.purchase')) }}" />
                                @endhaspurchaseordersenabled
                            @endif
                        @endif
                    @endcan
                    @can('view crm orders')
                        @if(! $subTotalError && ! $taxError && ! $totalError)
                            <x-mary-button icon="o-arrow-down-tray" link="{{ url(route('laravel-crm.orders.download', $order)) }}" class="btn-sm btn-square btn-outline" />
                        @endif
                        <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.orders.show', $order)) }}" class="btn-sm btn-square btn-outline" />
                    @endcan
                    @can('edit crm orders')
                        <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.orders.edit', $order)) }}" class="btn-sm btn-square btn-outline" />
                    @endcan
                    @can('delete crm orders')
                        <x-mary-button onclick="modalDeleteOrder{{ $order->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                        <x-crm-delete-confirm model="order" id="{{ $order->id }}" />
                    @endcan
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>

