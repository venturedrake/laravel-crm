<div>
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.leads')) }}" class="mb-5" progress-indicator >
        {{--  SEARCH --}}
        <x-slot:middle class="!justify-end">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.leads')) }}..." wire:model.live.debounce="name" icon="o-magnifying-glass" class="input-neutral" clearable />
        </x-slot:middle>

        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-mary-button label="Filters"
                           icon="o-funnel"
                           :badge="$filterCount ?? 0"
                           badge-classes="font-mono text-white"
                           @click="$wire.showFilters = true"
                           class="btn-outline"
                           responsive />

            <x-crm-index-toggle :layout="$layout" model="leads"/>

            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_lead')) }}" link="{{ url(route('laravel-crm.leads.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
        </x-slot:actions>
    </x-mary-header>

    {{-- TABLE --}}
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$leads" link="/leads/{id}/edit" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            {{-- Cover image scope --}}
            @scope('cell_labels', $lead)
                @foreach($lead->labels as $label)
                    <x-mary-badge value="{{ $label->name }}" class="text-white" style="border-color: #{{ $label->hex }}; background-color: #{{ $label->hex }}" />
                @endforeach 
            @endscope
            @scope('cell_pipeline_stage', $lead)
                <x-mary-badge :value="$lead->pipelineStage->name" class="badge badge-primary text-white" {{--:class="$order->status->color"--}} />
            @endscope}
        </x-mary-table>
    </x-mary-card>

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
</div>
