<div class="crm-content">
    {{-- HEADER --}}
    <x-crm-header title="{{ $delivery->title }}" class="mb-5" progress-indicator >
        <x-slot:badges>
            @if($delivery->pipelineStage)
                <x-mary-badge :value="$delivery->pipelineStage->name" class="badge badge-neutral text-white" />
            @endif
        </x-slot:badges>
        
        {{-- ACTIONS --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_deliveries')) }}" link="{{ url(route('laravel-crm.deliveries.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> |
            @can('view crm deliveries')
                <x-mary-button icon="o-arrow-down-tray" link="{{ url(route('laravel-crm.deliveries.download', $delivery)) }}" class="btn-sm btn-square btn-outline" />
            @endcan
            | <livewire:crm-activity-menu /> |
            @can('edit crm deliveries')
                <x-mary-button link="{{ url(route('laravel-crm.deliveries.edit', $delivery)) }}" icon="o-pencil-square" class="btn-sm btn-square btn-outline" responsive />
            @endcan
            @can('delete crm deliveries')
                <x-mary-button onclick="modalDeleteDelivery{{ $delivery->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                <x-crm-delete-confirm model="delivery" id="{{ $delivery->id }}" />           
            @endcan
        </x-slot:actions>
    </x-crm-header>
    <div class="grid lg:grid-cols-2 gap-5 items-start">
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
                <div class="grid gap-y-3">
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.created')) }}</strong>
                        <span>
                        {{ $delivery->created_at->diffForHumans() }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.number')) }}</strong>
                        <span>
                        {{ $delivery->delivery_id }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.reference')) }}</strong>
                        <span>
                        {{ $delivery->reference }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucwords(__('laravel-crm::lang.delivery_expected')) }}</strong>
                        <span>
                        {{ $delivery->delivery_expected ? $delivery->delivery_expected->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucwords(__('laravel-crm::lang.delivered_on')) }}</strong>
                        <span>
                        {{ $delivery->delivered_on ? $delivery->delivered_on->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null }}
                        </span>
                    </div>
                    @hasordersenabled
                    @if($delivery->order)
                        <div class="flex flex-row gap-5">
                            <strong>{{ ucfirst(__('laravel-crm::lang.order')) }}</strong>
                            <span>
                                <a href="{{ route('laravel-crm.orders.show', $delivery->order) }}" class="link link-hover link-primary">{{ $delivery->order->order_id }}</a>
                            </span>
                        </div>
                    @endif
                    @endhasordersenabled
                    @if($this->address)
                        <div class="flex flex-row gap-5">
                            <strong>{{ ($this->address->addressType) ? ucfirst($this->address->addressType->name).' ' : null }}{{ ucfirst(__('laravel-crm::lang.address')) }}</strong>
                            <span>
                               {{ \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($this->address) }}
                            </span>
                        </div>
                    @endif
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.owner')) }}</strong>
                        <span>
                        @if( $delivery->ownerUser)<a href="{{ route('laravel-crm.users.show', $delivery->ownerUser) }}" class="link link-hover link-primary">{{ $delivery->ownerUser->name ?? null }}</a> @else  {{ ucfirst(__('laravel-crm::lang.unallocated')) }} @endif
                        </span>
                    </div>
                </div>
            </x-mary-card>
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.delivery_items')) }} ({{ $delivery->deliveryProducts->count() }})" shadow separator>
                <div class="grid gap-y-5">
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col" class="px-0">{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                            <th scope="col" class="text-center w-30">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($delivery->deliveryProducts()->whereNotNull('order_product_id')->orderBy('order', 'asc')->orderBy('created_at', 'asc')->get() as $deliveryProduct)
                            <tr>
                                <td class="px-0">
                                    {{ $deliveryProduct->orderProduct->product->name }}
                                    @if($deliveryProduct->orderProduct->product->code)
                                        <br /><small>{{ $deliveryProduct->orderProduct->product->code }}</small>
                                    @endif
                                </td>
                                <td class="text-center w-30">{{ $deliveryProduct->quantity }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-mary-card>
        </div>
        <div>
            <livewire:crm-activity-tabs :model="$delivery" />
        </div>
    </div>
</div>
