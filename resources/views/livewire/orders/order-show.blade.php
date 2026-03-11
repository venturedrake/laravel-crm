<div class="crm-content">
    {{-- HEADER --}}
    <x-crm-header title="{{ $order->title }}" class="mb-5" progress-indicator >
        <x-slot:badges>
            @if($order->pipelineStage)
                <x-mary-badge :value="$order->pipelineStage->name" class="badge badge-neutral text-white" />
            @endif
        </x-slot:badges>
        
        {{-- ACTIONS --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_orders')) }}" link="{{ url(route('laravel-crm.orders.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> |
                @php 
                    (! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\subTotal($order)) ? $subTotalError = true : $subTotalError = false;
                    (! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\tax($order)) ? $taxError = true : $taxError = false;
                    (! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\total($order)) ? $totalError = true : $totalError = false;
                @endphp
                @can('edit crm orders')
                    @if($subTotalError || $taxError || $totalError)
                        <x-mary-button link="{{ url(route('laravel-crm.orders.edit', $order)) }}" class="btn-sm btn-warning" label="Error with order, check amounts" />
                    @else
                        @if(! $order->deliveryComplete())
                            @haspurchaseordersenabled
                            <x-mary-button link="{{ route('laravel-crm.purchase-orders.create',['model' => 'order', 'id' => $order->id]) }}" class="btn-sm btn-success text-white"  label="{{ ucfirst(__('laravel-crm::lang.purchase')) }}" />
                            @endhaspurchaseordersenabled
                        @endif
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
                    @endif
                @endcan
             @can('view crm orders')
                @if(! $subTotalError && ! $taxError && ! $totalError)
                    <x-mary-button icon="o-arrow-down-tray" link="{{ url(route('laravel-crm.orders.download', $order)) }}" class="btn-sm btn-square btn-outline" />
                @endif
            @endcan
            | <livewire:crm-activity-menu /> |
            @can('edit crm orders')
                <x-mary-button link="{{ url(route('laravel-crm.orders.edit', $order)) }}" icon="o-pencil-square" class="btn-sm btn-square btn-outline" responsive />
            @endcan
            @can('delete crm orders')
                <x-mary-button onclick="modalDeleteOrder{{ $order->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                <x-crm-delete-confirm model="order" id="{{ $order->id }}" />           
            @endcan
        </x-slot:actions>
    </x-crm-header>
    <div class="grid lg:grid-cols-2 gap-5">
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
                <div class="grid gap-y-3">
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.created')) }}</strong>
                        <span>
                        {{ $order->created_at->diffForHumans() }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.number')) }}</strong>
                        <span>
                        {{ $order->order_id }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.reference')) }}</strong>
                        <span>
                        {{ $order->reference }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.description')) }}</strong>
                        <span>
                        {{ $order->description }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.labels')) }}</strong>
                        <span>
                        @foreach($order->labels as $label)
                                <x-mary-badge value="{{ $label->name }}" class="badge-sm text-white" style="border-color: #{{ $label->hex }}; background-color: #{{ $label->hex }}" />
                            @endforeach
                    </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.owner')) }}</strong>
                        <span>
                        @if( $order->ownerUser)<a href="{{ route('laravel-crm.users.show', $order->ownerUser) }}" class="link link-hover link-primary">{{ $order->ownerUser->name ?? null }}</a> @else  {{ ucfirst(__('laravel-crm::lang.unallocated')) }} @endif
                        </span>
                    </div>
                </div>
            </x-mary-card>
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.contact')) }}" shadow separator>
                <div class="grid gap-y-5">
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.user-circle" />
                        <span>
                            @if($order->person)<a href="{{ route('laravel-crm.people.show',$order->person) }}" class="link link-hover link-primary">{{ $order->person->name }}</a>@endif
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
                            @if($order->organization)<a href="{{ route('laravel-crm.organizations.show',$order->organization) }}">{{ $order->organization->name }}</a>@endif
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
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.order_items')) }} ({{ $order->orderProducts->count() }})" shadow separator>
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
                        @foreach($order->orderProducts()->whereNotNull('product_id')->orderBy('order', 'asc')->orderBy('created_at', 'asc')->get() as $orderProduct)
                            <tr>
                                <td class="px-0">
                                    {{ $orderProduct->product->name }}
                                    @if($orderProduct->product->code)
                                        <br /><small>{{ $orderProduct->product->code }}</small>
                                    @endif
                                </td>
                                <td>{{ money($orderProduct->price ?? null, $orderProduct->currency) }}</td>
                                <td>{{ $orderProduct->quantity }}</td>
                                <td>{{ money($orderProduct->tax_amount ?? null, $orderProduct->currency) }}</td>
                                <td>
                                    @if(! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\lineAmount($orderProduct))
                                        <span data-toggle="tooltip" data-placement="top" title="Error with amount" class="text-danger">
                                    {{ money($orderProduct->amount ?? null, $orderProduct->currency) }}
                                    </span>
                                    @else
                                        {{ money($orderProduct->amount ?? null, $orderProduct->currency) }}
                                    @endif
                                </td>
                            </tr>
                            @if($orderProduct->comments)
                                <tr>
                                    <td colspan="5" class="border-0 pt-0">
                                        <strong>{{ ucfirst(__('laravel-crm::lang.comments')) }}</strong><br />
                                        {{ $orderProduct->comments }}
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
                                @if(! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\subTotal($order))
                                    <span data-toggle="tooltip" data-placement="top" title="Error with sub total" class="text-danger">
                                     {{ money($order->subtotal, $order->currency) }}
                                    </span>
                                @else
                                    {{ money($order->subtotal, $order->currency) }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.discount')) }}</strong></td>
                            <td>{{ money($order->discount, $order->currency) }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.tax')) }}</strong></td>
                            <td>
                                @if(! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\tax($order))
                                    <span data-toggle="tooltip" data-placement="top" title="Error with tax" class="text-danger">
                                     {{ money($order->tax, $order->currency) }}
                                    </span>
                                @else
                                    {{ money($order->tax, $order->currency) }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.adjustment')) }}</strong></td>
                            <td>{{ money($order->adjustments, $order->currency) }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.total')) }}</strong></td>
                            <td>
                                @if(! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\total($order))
                                    <span data-toggle="tooltip" data-placement="top" title="Error with total" class="text-danger">
                                    {{ money($order->total, $order->currency) }}
                                    </span>
                                @else
                                    {{ money($order->total, $order->currency) }}
                                @endif
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </x-mary-card>
        </div>
        <div>
            <livewire:crm-activity-tabs :model="$order" />
        </div>
    </div>
</div>
