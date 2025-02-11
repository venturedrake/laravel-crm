<x-crm::app-layout>
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.leads')) }}" separator progress-indicator>
        {{--  SEARCH --}}
        <x-slot:middle class="!justify-end">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.leads')) }}..." wire:model.live.debounce="name" icon="o-magnifying-glass" clearable />
        </x-slot:middle>

        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-mary-button label="Filters"
                      icon="o-funnel"
                      :badge="$filterCount ?? 0"
                      badge-classes="font-mono text-white"
                      @click="$wire.showFilters = true"
                      class=""
                      responsive />

            <div class="join">
                @php $model = 'leads'; @endphp
                <x-mary-button icon="fas.list" class="join-item btn-active" link="{{ route('laravel-crm.'.$model.'.list') }}" />
                <x-mary-button icon="fas.th" class="join-item" link="{{ route('laravel-crm.'.$model.'.board') }}" />
            </div>

            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_lead')) }}" link="{{ url(route('laravel-crm.leads.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
        </x-slot:actions>
    </x-mary-header>

    <livewire:crm-lead-board />

    {{-- TABLE --}}
    {{--<x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$orders" link="/orders/{id}/edit" with-pagination :sort-by="$sortBy">
            @scope('cell_status', $order)
            <x-mary-badge :value="$order->status->name" :class="$order->status->color" />
            @endscope
        </x-mary-table>
    </x-mary-card>--}}

    {{-- FILTERS --}}
    {{--<x-mary-drawer wire:model="showFilters" title="Filters" class="lg:w-1/3" right separator with-close-button>
        <div class="grid gap-5" @keydown.enter="$wire.showFilters = false">
            <x-mary-input label="Customer ..." wire:model.live.debounce="name" icon="o-user" inline />
            <x-mary-select label="Status" :options="$statuses" wire:model.live="status_id" icon="o-map-pin" placeholder="All" placeholder-value="0" inline />
            <x-mary-select label="Country" :options="$countries" wire:model.live="country_id" icon="o-flag" placeholder="All" placeholder-value="0" inline />
        </div>

        --}}{{-- ACTIONS --}}{{--
        <x-slot:actions>
            <x-mary-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-mary-button label="Done" icon="o-check" class="btn-primary" @click="$wire.showFilters = false" />
        </x-slot:actions>
    </x-mary-drawer>--}}
</x-crm::app-layout>
