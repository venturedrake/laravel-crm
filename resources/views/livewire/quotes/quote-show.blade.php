<div class="crm-content">
    {{-- HEADER --}}
    <x-crm-header title="{{ $quote->title }}" class="mb-5" progress-indicator >
        <x-slot:badges>
            @if($quote->pipelineStage)
                <x-mary-badge :value="$quote->pipelineStage->name" class="badge badge-neutral text-white" />
            @endif
        </x-slot:badges>
        
        {{-- ACTIONS --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_quotes')) }}" link="{{ url(route('laravel-crm.quotes.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> |
            @php
                    (!\VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\subTotal($quote) || ! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\total($quote)) ? $quoteError = true : $quoteError = false;
            @endphp
                @if(! $quote->order && !$quoteError)
                    <livewire:crm-quote-send :key="'quote-send-'.$quote->id" :$quote />
                @endif
                @can('edit crm quotes')
                    @if($quoteError)
                        <x-mary-button link="{{ url(route('laravel-crm.quotes.edit', $quote)) }}" class="btn-sm btn-warning" label="Error with quote, check amounts" />
                    @else
                        @if(!$quote->accepted_at && !$quote->rejected_at)
                            <x-mary-button wire:click="accept({{ $quote->id }})" class="btn-sm btn-success text-white"  label="{{ ucfirst(__('laravel-crm::lang.accept')) }}" />
                            <x-mary-button wire:click="reject({{ $quote->id }})" class="btn-sm btn-error text-white" label="{{ ucfirst(__('laravel-crm::lang.reject')) }}" />
                        @elseif($quote->accepted_at && $quote->orders()->count() > 0 && ! $quote->orderComplete())
                            @hasordersenabled
                            <x-mary-button link="{{ route('laravel-crm.orders.create',['model' => 'quote', 'id' => $quote->id]) }}" class="btn-sm btn-success text-white"  label="{{ ucfirst(__('laravel-crm::lang.create_order')) }}" />
                            @endhasordersenabled
                        @elseif($quote->accepted_at && $quote->orders()->count() < 1)
                            <x-mary-button wire:click="unaccept({{ $quote->id }})" class="btn-sm btn-outline"  label="{{ ucfirst(__('laravel-crm::lang.unaccept')) }}" />
                            @hasordersenabled
                            <x-mary-button link="{{ route('laravel-crm.orders.create',['model' => 'quote', 'id' => $quote->id]) }}" class="btn-sm btn-success text-white"  label="{{ ucfirst(__('laravel-crm::lang.create_order')) }}" />
                            @endhasordersenabled
                        @elseif($quote->rejected_at)
                            <x-mary-button wire:click="unreject({{ $quote->id }})" class="btn-sm btn-outline"  label="{{ ucfirst(__('laravel-crm::lang.unreject')) }}" />
                        @endif
                    @endcan
                @endif
                @can('view crm quotes')
                    @if(! $quoteError)
                        <x-mary-button icon="o-arrow-down-tray" link="{{ url(route('laravel-crm.quotes.download', $quote)) }}" class="btn-sm btn-square btn-outline" />
                    @endif
                @endcan
            | <livewire:crm-activity-menu /> |
            @can('edit crm quotes')
                <x-mary-button link="{{ url(route('laravel-crm.quotes.edit', $quote)) }}" icon="o-pencil-square" class="btn-sm btn-square btn-outline" responsive />
            @endcan
            @can('delete crm quotes')
                <x-mary-button onclick="modalDeleteQuote{{ $quote->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                <x-crm-delete-confirm model="quote" id="{{ $quote->id }}" />           
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
                        {{ $quote->created_at->diffForHumans() }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.number')) }}</strong>
                        <span>
                        {{ $quote->quote_id }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.reference')) }}</strong>
                        <span>
                        {{ $quote->reference }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucwords(__('laravel-crm::lang.issue_date')) }}</strong>
                        <span>
                        {{ $quote->issue_at ? $quote->issue_at->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucwords(__('laravel-crm::lang.expiry_date')) }}</strong>
                        <span>
                        {{ $quote->expire_at ? $quote->expire_at->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.description')) }}</strong>
                        <span>
                        {{ $quote->description }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.labels')) }}</strong>
                        <span>
                        @foreach($quote->labels as $label)
                                <x-mary-badge :value="$label->name" class="badge-sm text-white" :style="'border-color: #'.$label->hex.'; background-color: #'.$label->hex" />
                            @endforeach
                    </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.owner')) }}</strong>
                        <span>
                        @if( $quote->ownerUser)<a href="{{ route('laravel-crm.users.show', $quote->ownerUser) }}" class="link link-hover link-primary">{{ $quote->ownerUser->name ?? null }}</a> @else  {{ ucfirst(__('laravel-crm::lang.unallocated')) }} @endif
                        </span>
                    </div>
                </div>
            </x-mary-card>
            <x-crm-custom-field-values :model="$quote" :group="true" />
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.contact')) }}" shadow separator>
                <div class="grid gap-y-5">
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.user-circle" />
                        <span>
                        @if($quote->person)<a href="{{ route('laravel-crm.people.show',$quote->person) }}" class="link link-hover link-primary">{{ $quote->person->name }}</a>@endif
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
                        @if($quote->organization)<a href="{{ route('laravel-crm.organizations.show',$quote->organization) }}">{{ $quote->organization->name }}</a>@endif
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
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.quote_items')) }} ({{ $quote->quoteProducts->count() }})" shadow separator>
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
                        @foreach($quote->quoteProducts()->whereNotNull('product_id')->orderBy('order', 'asc')->orderBy('created_at', 'asc')->get() as $quoteProduct)
                            <tr>
                                <td class="px-0">
                                    {{ $quoteProduct->product->name }}
                                    @if($quoteProduct->product->code)
                                        <br /><small>{{ $quoteProduct->product->code }}</small>
                                    @endif
                                </td>
                                <td>{{ money($quoteProduct->price ?? null, $quoteProduct->currency) }}</td>
                                <td>{{ $quoteProduct->quantity }}</td>
                                <td>{{ money($quoteProduct->tax_amount ?? null, $quoteProduct->currency) }}</td>
                                <td>
                                    @if(! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\lineAmount($quoteProduct))
                                        <span data-toggle="tooltip" data-placement="top" title="Error with amount" class="text-danger">
                                    {{ money($quoteProduct->amount ?? null, $quoteProduct->currency) }}
                                    </span>
                                    @else
                                        {{ money($quoteProduct->amount ?? null, $quoteProduct->currency) }}
                                    @endif
                                </td>
                            </tr>
                            @if($quoteProduct->comments)
                                <tr>
                                    <td colspan="5" class="border-0 pt-0">
                                        <strong>{{ ucfirst(__('laravel-crm::lang.comments')) }}</strong><br />
                                        {{ $quoteProduct->comments }}
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
                                @if(! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\subTotal($quote))
                                    <span data-toggle="tooltip" data-placement="top" title="Error with sub total" class="text-danger">
                                     {{ money($quote->subtotal, $quote->currency) }}
                                    </span>
                                @else
                                    {{ money($quote->subtotal, $quote->currency) }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.discount')) }}</strong></td>
                            <td>{{ money($quote->discount, $quote->currency) }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.tax')) }}</strong></td>
                            <td>
                                @if(! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\tax($quote))
                                    <span data-toggle="tooltip" data-placement="top" title="Error with tax" class="text-danger">
                                     {{ money($quote->tax, $quote->currency) }}
                                    </span>
                                @else
                                    {{ money($quote->tax, $quote->currency) }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.adjustment')) }}</strong></td>
                            <td>{{ money($quote->adjustments, $quote->currency) }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong>{{ ucfirst(__('laravel-crm::lang.total')) }}</strong></td>
                            <td>
                                @if(! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\total($quote))
                                    <span data-toggle="tooltip" data-placement="top" title="Error with total" class="text-danger">
                                    {{ money($quote->total, $quote->currency) }}
                                    </span>
                                @else
                                    {{ money($quote->total, $quote->currency) }}
                                @endif
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </x-mary-card>
        </div>
        <div>
            <livewire:crm-activity-tabs :model="$quote" />
        </div>
    </div>
</div>
