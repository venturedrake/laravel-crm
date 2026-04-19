<div class="crm-content">
    {{-- HEADER --}}
    <x-crm-header class="mb-5" progress-indicator >
        <x-slot:title>
            {{ $purchaseOrder->title }}
            @if($purchaseOrder->sent == 1)
                <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.sent')) }}" class="badge badge-sm badge-success text-white" />
            @endif
        </x-slot:title>
        <x-slot:badges>
            @if($purchaseOrder->pipelineStage)
                <x-mary-badge :value="$purchaseOrder->pipelineStage->name" class="badge badge-neutral text-white" />
            @endif
        </x-slot:badges>

        {{-- ACTIONS --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_purchase_orders')) }}" link="{{ url(route('laravel-crm.purchase-orders.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> |
            <livewire:crm-purchase-order-send :key="'purchase-order-send-'.$purchaseOrder->id" :$purchaseOrder />
            @can('view crm purchase orders')
                <x-mary-button icon="o-arrow-down-tray" link="{{ url(route('laravel-crm.purchase-orders.download', $purchaseOrder)) }}" class="btn-sm btn-square btn-outline" />
            @endcan
            | <livewire:crm-activity-menu /> |
            @if(! $purchaseOrder->xeroPurchaseOrder)
                @can('edit crm purchase orders')
                    <x-mary-button link="{{ url(route('laravel-crm.purchase-orders.edit', $purchaseOrder)) }}" icon="o-pencil-square" class="btn-sm btn-square btn-outline" responsive />
                @endcan
                @can('delete crm purchase orders')
                    <x-mary-button onclick="modalDeletePurchaseOrder{{ $purchaseOrder->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                    <x-crm-delete-confirm model="purchaseOrder" id="{{ $purchaseOrder->id }}" deleting="purchase order" />
                @endcan
            @endif
            @if($purchaseOrder->xeroPurchaseOrder)
                <img src="/vendor/laravel-crm/img/xero-icon.png" height="30" />
            @endif
        </x-slot:actions>
    </x-crm-header>
    <div class="grid lg:grid-cols-2 gap-5 items-start">
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
                <div class="grid gap-y-3">
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.created')) }}</strong>
                        <span>
                        {{ $purchaseOrder->created_at->diffForHumans() }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.number')) }}</strong>
                        <span>
                        {{ $purchaseOrder->purchase_order_id }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.reference')) }}</strong>
                        <span>
                        {{ $purchaseOrder->reference }}
                        </span>
                    </div>
                    @hasordersenabled
                        @if($purchaseOrder->order)
                            <div class="flex flex-row gap-5">
                                <strong>{{ ucfirst(__('laravel-crm::lang.order')) }}</strong>
                                <span>
                                    <a href="{{ route('laravel-crm.orders.show', $purchaseOrder->order) }}" class="link link-hover link-primary">{{ $purchaseOrder->order->order_id }}</a>
                                </span>
                            </div>
                        @endif
                    @endhasordersenabled
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucwords(__('laravel-crm::lang.issue_date')) }}</strong>
                        <span>
                        {{ $purchaseOrder->issue_date ? $purchaseOrder->issue_date->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucwords(__('laravel-crm::lang.delivery_date')) }}</strong>
                        <span>
                        {{ $purchaseOrder->delivery_date ? $purchaseOrder->delivery_date->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.terms')) }}</strong>
                        <span>
                        {{ $purchaseOrder->terms }}
                        </span>
                    </div>
                    @if($purchaseOrder->delivery_type == 'pickup')
                        <div class="flex flex-row gap-5">
                            <strong>{{ ucwords(__('laravel-crm::lang.delivery_type')) }}</strong>
                            <span>{{ ucfirst(__('laravel-crm::lang.pickup')) }}</span>
                        </div>
                    @else
                        <div class="flex flex-row gap-5">
                            <strong>{{ ucwords(__('laravel-crm::lang.delivery_type')) }}</strong>
                            <span>{{ ucfirst($purchaseOrder->delivery_type) }}</span>
                        </div>
                        @if($purchaseOrder->address)
                            <div class="flex flex-row gap-5">
                                <strong>{{ ucwords(__('laravel-crm::lang.delivery_contact')) }}</strong>
                                <span>{{ $purchaseOrder->address->contact }}</span>
                            </div>
                            <div class="flex flex-row gap-5">
                                <strong>{{ ucwords(__('laravel-crm::lang.delivery_phone')) }}</strong>
                                <span>{{ $purchaseOrder->address->phone}}</span>
                            </div>
                            <div class="flex flex-row gap-5">
                                <strong>{{ ucwords(__('laravel-crm::lang.delivery_address')) }}</strong>
                                <span>{{ \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($purchaseOrder->address) }}</span>
                            </div>
                        @endif
                        @if($purchaseOrder->delivery_instructions)
                            <div class="flex flex-row gap-5">
                                <strong>{{ ucwords(__('laravel-crm::lang.delivery_instructions')) }}</strong>
                                <span>{{ $purchaseOrder->delivery_instructions }}</span>
                            </div>
                        @endif
                    @endif
                </div>
            </x-mary-card>
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.contact')) }}" shadow separator>
                <div class="grid gap-y-5">
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.user-circle" />
                        <span>
                            @if($purchaseOrder->person)<a href="{{ route('laravel-crm.people.show', $purchaseOrder->person) }}" class="link link-hover link-primary">{{ $purchaseOrder->person->name }}</a>@endif
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.envelope" />
                        <span>
                        @if($email)
                            <a href="mailto:{{ $email->address }}">{{ $email->address }}</a> ({{ ucfirst($email->type) }})
                        @endif
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.phone" />
                        <span>
                        @if($phone)
                            <a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> ({{ ucfirst($phone->type) }})
                        @endif
                        </span>
                    </div>
                </div>
            </x-mary-card>
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.organization')) }}" shadow separator>
                <div class="grid gap-y-5">
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.building" />
                        <span>
                            @if($purchaseOrder->organization)<a href="{{ route('laravel-crm.organizations.show', $purchaseOrder->organization) }}">{{ $purchaseOrder->organization->name }}</a>@endif
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.map-marker" />
                        <span>
                            {{ ($address) ? \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($address) : null }}
                        </span>
                    </div>
                </div>
            </x-mary-card>
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.purchase_order_lines')) }} ({{ $purchaseOrder->purchaseOrderLines->count() }})" shadow separator>
                <div class="grid gap-y-5">
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col" class="px-0">{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                            <th scope="col">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                            <th scope="col">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
                            <th scope="col">{{ $taxName }}</th>
                            <th scope="col">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($purchaseOrder->purchaseOrderLines()->whereNotNull('product_id')->orderBy('order', 'asc')->orderBy('created_at', 'asc')->get() as $purchaseOrderLine)
                            <tr>
                                <td class="px-0">
                                    {{ $purchaseOrderLine->product->name }}
                                    @if($purchaseOrderLine->product->code)
                                        <br /><small>{{ $purchaseOrderLine->product->code }}</small>
                                    @endif
                                </td>
                                <td>{{ money($purchaseOrderLine->price ?? null, $purchaseOrderLine->currency) }}</td>
                                <td>{{ $purchaseOrderLine->quantity }}</td>
                                <td>{{ money($purchaseOrderLine->tax_amount ?? null, $purchaseOrderLine->currency) }}</td>
                                <td>
                                    @if(! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\lineAmount($purchaseOrderLine))
                                        <span data-toggle="tooltip" data-placement="top" title="Error with amount" class="text-danger">
                                        {{ money($purchaseOrderLine->amount ?? null, $purchaseOrderLine->currency) }}
                                        </span>
                                    @else
                                        {{ money($purchaseOrderLine->amount ?? null, $purchaseOrderLine->currency) }}
                                    @endif
                                </td>
                            </tr>
                            @if($purchaseOrderLine->comments)
                                <tr>
                                    <td colspan="5" class="border-0 pt-0">
                                        <strong>{{ ucfirst(__('laravel-crm::lang.comments')) }}</strong><br />
                                        {{ $purchaseOrderLine->comments }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</strong></td>
                            <td>
                                {{ money($purchaseOrder->subtotal, $purchaseOrder->currency) }}
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.tax')) }}</strong></td>
                            <td>
                                {{ money($purchaseOrder->tax, $purchaseOrder->currency) }}
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.total')) }}</strong></td>
                            <td>
                                {{ money($purchaseOrder->total, $purchaseOrder->currency) }}
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </x-mary-card>
        </div>
        <div>
            <livewire:crm-activity-tabs :model="$purchaseOrder" />
        </div>
    </div>
</div>

