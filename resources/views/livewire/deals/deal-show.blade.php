<div class="crm-content">
    {{-- HEADER --}}
    <x-crm-header title="{{ $deal->title }}" class="mb-5" progress-indicator >
        <x-slot:badges>
            @if($deal->pipelineStage)
                <x-mary-badge :value="$deal->pipelineStage->name" class="badge badge-neutral text-white" />
            @endif
        </x-slot:badges>
        
        {{-- ACTIONS --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_deals')) }}" link="{{ url(route('laravel-crm.deals.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive />
            @hasdealsenabled
            @can('edit crm deals')
                | @if(!$deal->closed_at)
                    <x-mary-button wire:click="won({{ $deal->id }})" label="{{ ucfirst(__('laravel-crm::lang.won')) }}" class="btn-sm btn-success text-white" />
                    <x-mary-button wire:click="lost({{ $deal->id }})" label="{{ ucfirst(__('laravel-crm::lang.lost')) }}" class="btn-sm btn-error text-white" />
                @else
                    <x-mary-button wire:click="reopen({{ $deal->id }})" label="{{ ucfirst(__('laravel-crm::lang.reopen')) }}" class="btn-sm btn-outline" />
                @endif
            @endcan
            @endhasdealsenabled
            | <livewire:crm-activity-menu /> |
            @can('edit crm deals')
                <x-mary-button link="{{ url(route('laravel-crm.deals.edit', $deal)) }}" icon="o-pencil-square" class="btn-sm btn-square btn-outline" responsive />
            @endcan
            @can('delete crm deals')
                <x-mary-button onclick="modalDeleteDeal{{ $deal->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                <x-crm-delete-confirm model="deal" id="{{ $deal->id }}" />
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
                        {{ $deal->created_at->diffForHumans() }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.number')) }}</strong>
                        <span>
                        {{ $deal->deal_id }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.value')) }}</strong>
                        <span>
                       {{ money($deal->amount, $deal->currency) }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.description')) }}</strong>
                        <span>
                        {{ $deal->description }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucwords(__('laravel-crm::lang.expected_close')) }}</strong>
                        <span>
                        {{ $deal->expected_close ? $deal->expected_close->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.labels')) }}</strong>
                        <span>
                        @foreach($deal->labels as $label)
                                <x-mary-badge :value="$label->name" class="badge-sm text-white" :style="'border-color: #'.$label->hex.'; background-color: #'.$label->hex" />
                            @endforeach
                    </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.owner')) }}</strong>
                        <span>
                        @if( $deal->ownerUser)<a href="{{ route('laravel-crm.users.show', $deal->ownerUser) }}" class="link link-hover link-primary">{{ $deal->ownerUser->name ?? null }}</a> @else  {{ ucfirst(__('laravel-crm::lang.unallocated')) }} @endif
                        </span>
                    </div>
                </div>
            </x-mary-card>
            <x-crm-custom-field-values :model="$deal" :group="true" />
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.contact')) }}" shadow separator>
                <div class="grid gap-y-5">
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.user-circle" />
                        <span>
                        @if($deal->person)<a href="{{ route('laravel-crm.people.show',$deal->person) }}" class="link link-hover link-primary">{{ $deal->person->name }}</a>@endif
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
                        @if($deal->organization)<a href="{{ route('laravel-crm.organizations.show',$deal->organization) }}">{{ $deal->organization->name }}</a>@endif
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
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.products')) }} ({{ $deal->dealProducts->count() }})" shadow separator>
                <div class="grid gap-y-5">
                    <table class="table">
                        <thead>
                        <tr>
                            <th class="px-0">{{ ucfirst(__('laravel-crm::lang.name')) }}</th>
                            <th>{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                            <th>{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
                            <th>{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($deal->dealProducts()->whereNotNull('product_id')->get() as $dealProduct)
                                <tr> 
                                    <td class="px-0">{{ $dealProduct->product->name }}</td>
                                    <td>{{ money($dealProduct->price ?? null, $dealProduct->currency) }}</td>
                                    <td>{{ $dealProduct->quantity }}</td>
                                    <th>{{ money($dealProduct->amount ?? null, $dealProduct->currency) }}</th>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-mary-card>
        </div>
        <div>
            <livewire:crm-activity-tabs :model="$deal" />
        </div>
    </div>
</div>
