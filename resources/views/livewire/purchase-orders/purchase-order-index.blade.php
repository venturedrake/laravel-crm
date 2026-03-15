<div class="crm-content">
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.purchase_orders')) }}" progress-indicator>
        {{--  SEARCH --}}
        <x-slot:middle class="justify-end!">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.search_purchase_orders')) }}..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:middle>

        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-mary-button label="Filters"
                           icon="o-funnel"
                           :badge="$filterCount ?? 0"
                           badge-classes="font-mono badge-primary badge-soft"
                           @click="$wire.showFilters = true"
                           responsive />

           {{-- <x-crm-index-toggle :layout="$layout" model="purchase_orders"/>--}}

            @can('create crm purchase orders')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_purchase_order')) }}" link="{{ url(route('laravel-crm.purchase-orders.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
            @endcan    
        </x-slot:actions>
    </x-mary-header>

    {{-- TABLE --}}
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$purchaseOrders" link="/purchase-orders/{id}" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('cell_pipeline_stage', $purchaseOrder)
                @if($purchaseOrder->pipelineStage)
                    <x-mary-badge :value="$purchaseOrder->pipelineStage->name" class="badge badge-neutral text-white" />
                @endif
            @endscope
            @scope('cell_order', $purchaseOrder)
            @if($purchaseOrder->order)
                <a href="{{ route('laravel-crm.orders.show', $purchaseOrder->order) }}">{{ $purchaseOrder->order->order_id }}</a>
            @endif
            @endscope
            @scope('cell_sent', $purchaseOrder)
                @if($purchaseOrder->sent != 1)
                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.sent')) }}" class="badge badge-success text-white" />
                @endif
            @endscope
            @scope('actions', $purchaseOrder)
            <div class="flex gap-1 justify-end">
                <livewire:crm-purchase-order-send :key="'purchase-order-send-'.$purchaseOrder->id" :$purchaseOrder />
                @can('view crm purchase orders')
                    <x-mary-button icon="o-arrow-down-tray" link="{{ url(route('laravel-crm.purchase-orders.download', $purchaseOrder)) }}" class="btn-sm btn-square btn-outline" />
                    <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.purchase-orders.show', $purchaseOrder)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @if(! $purchaseOrder->xeroPurchaseOrder)
                    @can('edit crm purchase orders')
                        <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.purchase-orders.edit', $purchaseOrder)) }}" class="btn-sm btn-square btn-outline" />
                    @endcan
                    @can('delete crm purchase orders')
                        <x-mary-button onclick="modalDeletePurchaseOrder{{ $purchaseOrder->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                        <x-crm-delete-confirm model="purchaseOrder" id="{{ $purchaseOrder->id }}" deleting="purchase order" />
                    @endcan
                @endif
                @if($purchaseOrder->xeroPurchaseOrder)
                    <img src="/vendor/laravel-crm/img/xero-icon.png" height="30" />
                @endif
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
