<div>
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$this->purchaseOrders" :link="route('laravel-crm.purchase-orders.show', ['purchaseOrder' => '[id]'])" class="whitespace-nowrap">
            @scope('cell_pipeline_stage', $purchaseOrder)
                @if($purchaseOrder->pipelineStage)
                    <x-mary-badge :value="$purchaseOrder->pipelineStage->name" class="badge badge-neutral text-white" />
                @endif
            @endscope
            @scope('cell_sent', $purchaseOrder)
                @if($purchaseOrder->sent == 1)
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
</div>

