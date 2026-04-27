<div>
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$this->deliveries" :link="route('laravel-crm.deliveries.show', ['delivery' => '[id]'])" class="whitespace-nowrap">
            @scope('cell_labels', $delivery)
                @foreach($delivery->labels as $label)
                    <x-mary-badge :value="$label->name" class="text-white" :style="'border-color: #'.$label->hex.'; background-color: #'.$label->hex" />
                @endforeach
            @endscope
            @scope('cell_order', $delivery)
                @if($delivery->order)
                    <a href="{{ route('laravel-crm.orders.show', $delivery->order) }}" class="link link-hover link-primary">{{ $delivery->order->order_id }}</a>
                @endif
            @endscope
            @scope('cell_address', $delivery)
                @if($address = $delivery->getShippingAddress())
                    {{ \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($address) }}
                @endif
            @endscope
            @scope('cell_pipeline_stage', $delivery)
                @if($delivery->pipelineStage)
                    <x-mary-badge :value="$delivery->pipelineStage->name" class="badge badge-neutral text-white" />
                @endif
            @endscope
            @scope('actions', $delivery)
                <div class="flex gap-1 justify-end">
                    @can('view crm deliveries')
                        <x-mary-button icon="o-arrow-down-tray" link="{{ url(route('laravel-crm.deliveries.download', $delivery)) }}" class="btn-sm btn-square btn-outline" />
                        <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.deliveries.show', $delivery)) }}" class="btn-sm btn-square btn-outline" />
                    @endcan
                    @can('edit crm deliveries')
                        <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.deliveries.edit', $delivery)) }}" class="btn-sm btn-square btn-outline" />
                    @endcan
                    @can('delete crm deliveries')
                        <x-mary-button onclick="modalDeleteDelivery{{ $delivery->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                        <x-crm-delete-confirm model="delivery" id="{{ $delivery->id }}" />
                    @endcan
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>

