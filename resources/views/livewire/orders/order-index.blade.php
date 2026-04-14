<div class="crm-content">
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.orders')) }}" progress-indicator>
        {{--  SEARCH --}}
        <x-slot:middle class="justify-end!">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.search_orders')) }}..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:middle>

        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-mary-button label="Filters"
                           icon="o-funnel"
                           :badge="$filterCount ?? 0"
                           badge-classes="font-mono badge-primary badge-soft"
                           @click="$wire.showFilters = true"
                           responsive />

            {{-- <x-crm-index-toggle :layout="$layout" model="orders"/>--}}
            @can('edit crm orders')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_order')) }}" link="{{ url(route('laravel-crm.orders.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    {{-- TABLE --}}
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$orders" :link="route('laravel-crm.orders.show', ['order' => '[id]'])" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('cell_labels', $order)
                @foreach($order->labels as $label)
                    <x-mary-badge value="{{ $label->name }}" class="text-white" style="border-color: #{{ $label->hex }}; background-color: #{{ $label->hex }}" />
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
            @scope('cell_pipeline_stage', $order)
                @if($order->pipelineStage)
                    <x-mary-badge :value="$order->pipelineStage->name" class="badge badge-neutral text-white" />
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
                            <x-mary-button link="{{ url(route('laravel-crm.orders.edit', $order)) }}" class="btn-sm btn-warning" label="Error with order, check amounts" />
                        @else
                            @if(! $order->invoiceComplete())
                                @hasinvoicesenabled
                                    <x-mary-button link="{{ route('laravel-crm.invoices.create',['model' => 'order', 'id' => $order->id]) }}" class="btn-sm btn-success text-white"  label="{{ ucfirst(__('laravel-crm::lang.invoice')) }}" />
                                @endhasinvoicesenabled
                            @endif
                            @if(! $order->deliveryComplete())
                                @hasdeliveriesenabled
                                    <x-mary-button link="{{ route('laravel-crm.deliveries.create',['model' => 'order', 'id' => $order->id]) }}" class="btn-sm btn-success text-white"  label="{{ ucfirst(__('laravel-crm::lang.delivery')) }}" />
                                @endhasdeliveriesenabled
                            @endif
                            @if(! $order->deliveryComplete())
                                @haspurchaseordersenabled
                                    <x-mary-button link="{{ route('laravel-crm.purchase-orders.create',['model' => 'order', 'id' => $order->id]) }}" class="btn-sm btn-success text-white"  label="{{ ucfirst(__('laravel-crm::lang.purchase')) }}" />
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

    {{-- FILTERS --}}
    <x-mary-drawer wire:model="showFilters" title="Filters" class="lg:w-1/3" right separator with-close-button>
        <div class="grid gap-5" @keydown.enter="$wire.showFilters = false">
            <x-mary-choices label="Owner" wire:model.live="user_id" :options="$users" icon="o-user" inline allow-all />
            <x-mary-choices label="Label" wire:model.live="label_id" :options="$labels" icon="o-tag" inline allow-all />
        </div>

        {{-- ACTIONS --}}
        <x-slot:actions>
            <x-mary-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-mary-button label="Done" icon="o-check" class="btn-primary text-white" @click="$wire.showFilters = false" />
        </x-slot:actions>
    </x-mary-drawer>
</div>
