<div class="crm-content">
    {{-- HEADER --}}
    <x-crm-header class="mb-5" progress-indicator >
        <x-slot:title>
            {{ $invoice->title }}
            @if($invoice->sent != 1)
                <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.sent')) }}" class="badge badge-sm badge-success text-white" />
            @endif
            @if($invoice->fully_paid_at)
                <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.paid')) }}" class="badge badge-sm badge-success text-white" />
            @elseif(! $invoice->fully_paid_at && $invoice->due_date->isToday())
                <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.due_today')) }}" class="badge badge-sm badge-secondary text-white" />
            @elseif(! $invoice->fully_paid_at && $invoice->due_date->isTomorrow())
                <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.due_tomorrow')) }}" class="badge badge-sm badge-secondary text-white" />
            @elseif(! $invoice->fully_paid_at && $invoice->due_date->isYesterday())
                <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.due_yesterday')) }}" class="badge badge-sm badge-secondary text-white" />
            @elseif(! $invoice->fully_paid_at && abs($invoice->due_date->diffinDays()) > 0  && $invoice->due_date >= \Carbon\Carbon::now()->timezone($timezone))
                <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.due')) }} {{ $invoice->due_date->diffForHumans() }}" class="badge badge-sm badge-secondary text-white" />
            @elseif(! $invoice->fully_paid_at && abs($invoice->due_date->diffinDays()) > 0  && $invoice->due_date < \Carbon\Carbon::now()->timezone($timezone))
                <x-mary-badge value="{{ $invoice->due_date->diffForHumans() }} {{ ucfirst(__('laravel-crm::lang.overdue')) }}" class="badge badge-sm badge-error text-white" />
            @endif
        </x-slot:title>
        <x-slot:badges>
            @if($invoice->pipelineStage)
                <x-mary-badge :value="$invoice->pipelineStage->name" class="badge badge-neutral text-white" />
            @endif
        </x-slot:badges>
        
        {{-- ACTIONS --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_invoices')) }}" link="{{ url(route('laravel-crm.invoices.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> |
                {{--@php 
                    (! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\subTotal($invoice)) ? $subTotalError = true : $subTotalError = false;
                    (! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\tax($invoice)) ? $taxError = true : $taxError = false;
                    (! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\total($invoice)) ? $totalError = true : $totalError = false;
                @endphp
                @can('edit crm invoices')
                    @if($subTotalError || $taxError || $totalError)
                        <x-mary-button link="{{ url(route('laravel-crm.invoices.edit', $invoice)) }}" class="btn-sm btn-warning" label="Error with order, check amounts" />
                    @else
                        @if(! $invoice->deliveryComplete())
                            @haspurchaseinvoicesenabled
                            <x-mary-button link="{{ route('laravel-crm.purchase-invoices.create',['model' => 'order', 'id' => $invoice->id]) }}" class="btn-sm btn-success text-white"  label="{{ ucfirst(__('laravel-crm::lang.purchase')) }}" />
                            @endhaspurchaseinvoicesenabled
                        @endif
                        @if(! $invoice->invoiceComplete())
                            @hasinvoicesenabled
                            <x-mary-button link="{{ route('laravel-crm.invoices.create',['model' => 'order', 'id' => $invoice->id]) }}" class="btn-sm btn-success text-white"  label="{{ ucfirst(__('laravel-crm::lang.invoice')) }}" />
                            @endhasinvoicesenabled
                        @endif
                        @if(! $invoice->deliveryComplete())
                            @hasdeliveriesenabled
                            <x-mary-button link="{{ route('laravel-crm.deliveries.create',['model' => 'order', 'id' => $invoice->id]) }}" class="btn-sm btn-success text-white"  label="{{ ucfirst(__('laravel-crm::lang.delivery')) }}" />
                            @endhasdeliveriesenabled
                        @endif
                    @endif
                @endcan
             @can('view crm invoices')
                @if(! $subTotalError && ! $taxError && ! $totalError)
                    <x-mary-button icon="o-arrow-down-tray" link="{{ url(route('laravel-crm.invoices.download', $invoice)) }}" class="btn-sm btn-square btn-outline" />
                @endif
            @endcan--}}
            <livewire:crm-invoice-send :key="'invoice-send-'.$invoice->id" :$invoice />
            @if(! $invoice->xeroInvoice)
                @if(! $invoice->fully_paid_at)
                    <livewire:crm-invoice-pay :key="'invoice-pay-'.$invoice->id" :$invoice />
                @endif
            @endif 
            @can('view crm invoices')
                <x-mary-button icon="o-arrow-down-tray" link="{{ url(route('laravel-crm.invoices.download', $invoice)) }}" class="btn-sm btn-square btn-outline" />
            @endcan
            | <livewire:crm-activity-menu /> |
            @if(! $invoice->xeroInvoice)
                @if($invoice->amount_paid <= 0)
                    @can('edit crm invoices')
                        <x-mary-button link="{{ url(route('laravel-crm.invoices.edit', $invoice)) }}" icon="o-pencil-square" class="btn-sm btn-square btn-outline" responsive />
                    @endcan
                    @can('delete crm invoices')
                        <x-mary-button onclick="modalDeleteInvoice{{ $invoice->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                        <x-crm-delete-confirm model="invoice" id="{{ $invoice->id }}" />           
                    @endcan
                @endif
            @endif
            @if($invoice->xeroInvoice)
                <img src="/vendor/laravel-crm/img/xero-icon.png" height="30" />
            @endif
        </x-slot:actions>
    </x-crm-header>
    <div class="grid lg:grid-cols-2 gap-5">
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
                <div class="grid gap-y-3">
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.created')) }}</strong>
                        <span>
                        {{ $invoice->created_at->diffForHumans() }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.number')) }}</strong>
                        <span>
                        {{ $invoice->invoice_id }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.reference')) }}</strong>
                        <span>
                        {{ $invoice->reference }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucwords(__('laravel-crm::lang.issue_date')) }}</strong>
                        <span>
                        {{ $invoice->issue_date ? $invoice->issue_date->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucwords(__('laravel-crm::lang.due_date')) }}</strong>
                        <span>
                        {{ $invoice->due_date ? $invoice->due_date->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.terms')) }}</strong>
                        <span>
                        {{ $invoice->terms }}
                        </span>
                    </div>
                </div>
            </x-mary-card>
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.contact')) }}" shadow separator>
                <div class="grid gap-y-5">
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.user-circle" />
                        <span>
                            @if($invoice->person)<a href="{{ route('laravel-crm.people.show',$invoice->person) }}" class="link link-hover link-primary">{{ $invoice->person->name }}</a>@endif
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
                            @if($invoice->organization)<a href="{{ route('laravel-crm.organizations.show',$invoice->organization) }}">{{ $invoice->organization->name }}</a>@endif
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
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.invoice_lines')) }} ({{ $invoice->invoiceLines->count() }})" shadow separator>
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
                        @foreach($invoice->invoiceLines()->whereNotNull('product_id')->orderBy('order', 'asc')->orderBy('created_at', 'asc')->get() as $invoiceProduct)
                            <tr>
                                <td class="px-0">
                                    {{ $invoiceProduct->product->name }}
                                    @if($invoiceProduct->product->code)
                                        <br /><small>{{ $invoiceProduct->product->code }}</small>
                                    @endif
                                </td>
                                <td>{{ money($invoiceProduct->price ?? null, $invoiceProduct->currency) }}</td>
                                <td>{{ $invoiceProduct->quantity }}</td>
                                <td>{{ money($invoiceProduct->tax_amount ?? null, $invoiceProduct->currency) }}</td>
                                <td>
                                    @if(! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\lineAmount($invoiceProduct))
                                        <span data-toggle="tooltip" data-placement="top" title="Error with amount" class="text-danger">
                                    {{ money($invoiceProduct->amount ?? null, $invoiceProduct->currency) }}
                                    </span>
                                    @else
                                        {{ money($invoiceProduct->amount ?? null, $invoiceProduct->currency) }}
                                    @endif
                                </td>
                            </tr>
                            @if($invoiceProduct->comments)
                                <tr>
                                    <td colspan="5" class="border-0 pt-0">
                                        <strong>{{ ucfirst(__('laravel-crm::lang.comments')) }}</strong><br />
                                        {{ $invoiceProduct->comments }}
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
                                {{ money($invoice->subtotal, $invoice->currency) }}
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.tax')) }}</strong></td>
                            <td>
                                {{ money($invoice->tax, $invoice->currency) }}
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.total')) }}</strong></td>
                            <td>
                                {{ money($invoice->total, $invoice->currency) }}
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </x-mary-card>
        </div>
        <div>
            <livewire:crm-activity-tabs :model="$invoice" />
        </div>
    </div>
</div>
