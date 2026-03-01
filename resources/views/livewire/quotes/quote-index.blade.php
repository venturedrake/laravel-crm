<div class="crm-content">
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.quotes')) }}" progress-indicator>
        {{--  SEARCH --}}
        <x-slot:middle class="justify-end!">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.search_quotes')) }}..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:middle>

        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-mary-button label="Filters"
                           icon="o-funnel"
                           :badge="$filterCount ?? 0"
                           badge-classes="font-mono badge-primary badge-soft"
                           @click="$wire.showFilters = true"
                           responsive />

            <x-crm-index-toggle :layout="$layout" model="quotes"/>

            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_quote')) }}" link="{{ url(route('laravel-crm.quotes.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
        </x-slot:actions>
    </x-mary-header>

    {{-- TABLE --}}
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$quotes" link="/quotes/{id}" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('cell_labels', $quote)
                @foreach($quote->labels as $label)
                    <x-mary-badge value="{{ $label->name }}" class="text-white" style="border-color: #{{ $label->hex }}; background-color: #{{ $label->hex }}" />
                @endforeach 
            @endscope
            @scope('cell_pipeline_stage', $quote)
                @if($quote->pipelineStage)
                    <x-mary-badge :value="$quote->pipelineStage->name" class="badge badge-neutral text-white" />
                @endif
            @endscope
            @scope('actions', $quote)
                @php
                    (!\VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\subTotal($quote) || ! \VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\total($quote)) ? $quoteError = true : $quoteError = false;
                @endphp
                <div class="flex gap-1">
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
                    <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.quotes.show', $quote)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('edit crm quotes')
                    @if(! $quote->accepted_at)
                        <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.quotes.edit', $quote)) }}" class="btn-sm btn-square btn-outline" />
                    @endif
                @endcan
                @can('delete crm quotes')
                <x-mary-button onclick="modalDeleteQuote{{ $quote->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                <x-crm-delete-confirm model="quote" id="{{ $quote->id }}" />
                @endcan
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
