<div>
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.leads')) }}" class="mb-5" progress-indicator >
        {{--  SEARCH --}}
        <x-slot:middle class="!justify-end">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.leads')) }}..." wire:model.live.debounce="search" icon="o-magnifying-glass" class="input-neutral" clearable />
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
        <x-mary-table :headers="$headers" :rows="$leads" link="/leads/{id}" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('cell_labels', $lead)
                @foreach($lead->labels as $label)
                    <x-mary-badge value="{{ $label->name }}" class="text-white" style="border-color: #{{ $label->hex }}; background-color: #{{ $label->hex }}" />
                @endforeach 
            @endscope
            @scope('cell_pipeline_stage', $lead)
                <x-mary-badge :value="$lead->pipelineStage->name" class="badge badge-primary text-white" />
            @endscope
            @scope('actions', $brand)
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.convert')) }}" class="btn-sm btn-success text-white" />
            <x-mary-button icon="o-eye" class="btn-sm btn-outline" />
            <x-mary-button icon="o-pencil-square" class="btn-sm btn-outline" />
            <x-mary-button wire:click="delete({{ $brand->id }})" icon="o-trash" class="btn-sm btn-error text-white" wire:confirm="Are you sure?" spinner />
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
            <x-mary-button label="Done" icon="o-check" class="btn-primary" @click="$wire.showFilters = false" />
        </x-slot:actions>
    </x-mary-drawer>
</div>
