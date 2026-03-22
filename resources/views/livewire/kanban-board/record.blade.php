<div class="card bg-base-100 rounded-lg p-5 mb-3 cursor-grab" id="{{ $record['id'] }}">
    <div class="grow-1 null">
        <div class="flex justify-between">
            <div>
                <span>{{ $record['title'] }}</span>
                @if($record['labels'])
                    <div class="mt-2">
                        @foreach($record['labels'] as $label)
                            <x-mary-badge value="{{ $label->name }}" class="text-white" style="border-color: #{{ $label->hex }}; background-color: #{{ $label->hex }}" />
                        @endforeach
                    </div>
                @endif
                <div class="mt-2">
                    <a href="{{ url(route('laravel-crm.'.\Illuminate\Support\Str::plural($model).'.show', $record['id'])) }}" class="link link-hover link-primary">{{ $record['number'] }}</a>
                </div>
            </div>
            <div class="ml-2">
                @switch($model)
                    @case('lead')
                        @canany(['view crm leads', 'edit crm leads', 'delete crm leads'])
                            <x-mary-dropdown class="btn-xs btn-square" right>
                                @can('edit crm leads')
                                    <li>
                                        <a class="my-0.5 py-1.5 px-4 hover:text-inherit whitespace-nowrap" href="{{ route('laravel-crm.deals.create', ['model' => 'lead', 'id' => $record['id']]) }}" wire:navigate draggable="false">
                                            <span class="mary-hideable whitespace-nowrap truncate">{{ ucfirst(__('laravel-crm::lang.convert')) }}</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('view crm leads')
                                    <x-mary-menu-item link="{{ route('laravel-crm.leads.show', ['lead' => $record['id']]) }}" title="{{ ucfirst(__('laravel-crm::lang.view')) }}" />
                                @endcan
                                @can('edit crm leads')
                                    <x-mary-menu-item link="{{ route('laravel-crm.leads.edit', ['lead' => $record['id']]) }}" title="{{ ucfirst(__('laravel-crm::lang.edit')) }}" />
                                @endcan
                                @can('delete crm leads')
                                    <x-mary-menu-item onclick="modalDeleteLead{{ $record['id'] }}.showModal()" title="{{ ucfirst(__('laravel-crm::lang.delete')) }}" />
                                @endcan
                            </x-mary-dropdown>
                        @endcanany
                    @break

                    @case('deal')
                        @canany(['view crm deals', 'edit crm deals', 'delete crm deals'])
                            <x-mary-dropdown class="btn-xs btn-square" right>
                                @can('edit crm deals')
                                    @if(!\VentureDrake\LaravelCrm\Models\Deal::find($record['id'])->closed_at)
                                        <x-mary-menu-item  wire:click="won({{ $record['id'] }})" title="{{ ucfirst(__('laravel-crm::lang.won')) }}" />
                                        <x-mary-menu-item  wire:click="lost({{ $record['id'] }})" title="{{ ucfirst(__('laravel-crm::lang.lost')) }}" />
                                    @else
                                        <x-mary-menu-item  wire:click="reopen({{ $record['id'] }})" title="{{ ucfirst(__('laravel-crm::lang.reopen')) }}" />
                                    @endif
                                @endcan
                                @can('view crm deals')
                                    <x-mary-menu-item link="{{ route('laravel-crm.deals.show', ['deal' => $record['id']]) }}" title="{{ ucfirst(__('laravel-crm::lang.view')) }}" />
                                @endcan
                                @can('edit crm deals')
                                    <x-mary-menu-item link="{{ route('laravel-crm.deals.edit', ['deal' => $record['id']]) }}" title="{{ ucfirst(__('laravel-crm::lang.edit')) }}" />
                                @endcan
                                @can('delete crm deals')
                                    <x-mary-menu-item onclick="modalDeleteDeal{{ $record['id'] }}.showModal()" title="{{ ucfirst(__('laravel-crm::lang.delete')) }}" />
                                @endcan
                            </x-mary-dropdown>
                        @endcanany
                        @break

                    @case('quote')
                        @canany(['view crm quotes', 'edit crm quotes', 'delete crm quotes'])
                            <x-mary-dropdown class="btn-xs btn-square" right>
                                @php 
                                    $quote = \VentureDrake\LaravelCrm\Models\Quote::find($record['id']);
                                    (!\VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\subTotal($quote) || ! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\total($quote)) ? $quoteError = true : $quoteError = false;
                                @endphp
                                @if(! $quote->order && !$quoteError)
                                    <x-mary-menu-item wire:click="send({{ $record['id'] }})" title="{{ ucfirst(__('laravel-crm::lang.send')) }}" />
                                @endif
                                @can('edit crm quotes')
                                    @if(! $quoteError)
                                        @if(!$quote->accepted_at && !$quote->rejected_at)
                                            <x-mary-menu-item wire:click="accept({{ $record['id'] }})" title="{{ ucfirst(__('laravel-crm::lang.accept')) }}" />
                                            <x-mary-menu-item wire:click="reject({{ $record['id'] }})" title="{{ ucfirst(__('laravel-crm::lang.reject')) }}" />
                                        @elseif($quote->accepted_at && $quote->orders()->count() > 0 && !$quote->orderComplete())
                                            @hasordersenabled
                                            <x-mary-menu-item link="{{ route('laravel-crm.orders.create',['model' => 'quote', 'id' => $quote->id]) }}" title="{{ ucfirst(__('laravel-crm::lang.create_order')) }}" />
                                            @endhasordersenabled
                                        @elseif($quote->accepted_at && $quote->orders()->count() < 1)
                                            <x-mary-menu-item wire:click="unaccept({{ $record['id'] }})" title="{{ ucfirst(__('laravel-crm::lang.unaccept')) }}" />
                                            @hasordersenabled
                                            <x-mary-menu-item link="{{ route('laravel-crm.orders.create',['model' => 'quote', 'id' => $quote->id]) }}" title="{{ ucfirst(__('laravel-crm::lang.create_order')) }}" />
                                            @endhasordersenabled
                                        @elseif($quote->rejected_at)
                                            <x-mary-menu-item wire:click="unreject({{ $record['id'] }})" title="{{ ucfirst(__('laravel-crm::lang.unreject')) }}" />
                                        @endif
                                    @endif
                                @endcan
                                @can('view crm quotes')
                                    @if(! $quoteError)
                                        <x-mary-menu-item link="{{ route('laravel-crm.quotes.download', ['quote' => $record['id']]) }}" title="{{ ucfirst(__('laravel-crm::lang.download')) }}" />
                                    @endif
                                    <x-mary-menu-item link="{{ route('laravel-crm.quotes.show', ['quote' => $record['id']]) }}" title="{{ ucfirst(__('laravel-crm::lang.view')) }}" />
                                @endcan
                                @can('edit crm quotes')
                                    @if(! $quote->accepted_at)
                                        <x-mary-menu-item link="{{ route('laravel-crm.quotes.edit', ['quote' => $record['id']]) }}" title="{{ ucfirst(__('laravel-crm::lang.edit')) }}" />
                                    @endif
                                @endcan
                                @can('delete crm quotes')
                                    <x-mary-menu-item onclick="modalDeleteQuote{{ $record['id'] }}.showModal()" title="{{ ucfirst(__('laravel-crm::lang.delete')) }}" />
                                @endcan
                            </x-mary-dropdown>
                            @if(! $quote->order && !$quoteError)
                                <livewire:crm-quote-send :key="'quote-send-'.$quote->id" :$quote type="menu-item" />
                            @endif
                        @endcanany
                        @break
                @endswitch
            </div>
        </div>
        <div class="flex justify-between mt-2">
            <div>
                @if($record['amount'])
                    {{ money($record['amount'], $record['currency']) }}
                @endif
            </div>
            <div>
                <x-mary-icon name="fas.user-circle" />
            </div>
        </div>
    </div>
</div>
<x-crm-delete-confirm model="{{ $model }}" id="{{ $record['id'] }}" />