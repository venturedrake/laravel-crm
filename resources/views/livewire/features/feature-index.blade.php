<div class="crm-content">
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.features')) }}" progress-indicator>
        <x-slot:middle class="justify-end!">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.features')) }}..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:middle>

        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.filters')) }}"
                           icon="o-funnel"
                           :badge="$filterCount ?? 0"
                           badge-classes="font-mono badge-primary badge-soft"
                           @click="$wire.showFilters = true"
                           responsive />

            <x-mary-button label="Board" link="{{ url(route('laravel-crm.features.board')) }}" icon="o-view-columns" class="btn" responsive />

            @can('create crm features')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.submit_feature')) }}" link="{{ url(route('laravel-crm.features.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    {{-- TABLE --}}
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$features" :link="route('laravel-crm.features.show', ['feature' => '[id]'])" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('cell_status.name', $feature)
                @if($feature->status)
                    <x-mary-badge :value="$feature->status->name" class="text-white" :style="'background-color: '.($feature->status->color ?? '#6c757d')" />
                @endif
            @endscope
            @scope('actions', $feature)
                <div class="flex gap-1 justify-end">
                    @if($feature->is_public)
                        <x-mary-button icon="o-arrow-top-right-on-square"
                                       link="{{ url(route('laravel-crm.portal.features.show', $feature)) }}"
                                       external
                                       title="{{ ucfirst(__('laravel-crm::lang.public')).' '.__('laravel-crm::lang.view') }}"
                                       class="btn-sm btn-square btn-outline" />
                    @endif
                    @can('edit crm features')
                        <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.features.edit', $feature)) }}" class="btn-sm btn-square btn-outline" />
                    @endcan
                    @can('view crm features')
                        <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.features.show', $feature)) }}" class="btn-sm btn-square btn-outline" />
                    @endcan
                    @can('delete crm features')
                        <x-mary-button onclick="modalDeleteFeature{{ $feature->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                        <x-crm-delete-confirm model="feature" id="{{ $feature->id }}" />
                    @endcan
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- FILTERS --}}
    <x-mary-drawer wire:model="showFilters" title="{{ ucfirst(__('laravel-crm::lang.filters')) }}" class="lg:w-1/3" right separator with-close-button>
        <div class="grid gap-5" @keydown.enter="$wire.showFilters = false">
            <x-mary-choices label="{{ ucfirst(__('laravel-crm::lang.status')) }}" wire:model.live="feature_status_id" :options="$statuses" icon="o-flag" inline allow-all />
            <x-mary-select label="Visibility" wire:model.live="is_public" :options="[
                ['id' => 1, 'name' => 'Public'],
                ['id' => 0, 'name' => 'Private'],
            ]" placeholder="Any" />
        </div>

        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.clear')) }}" icon="o-x-mark" wire:click="clear" spinner />
            <x-mary-button label="Done" icon="o-check" class="btn-primary" @click="$wire.showFilters = false" />
        </x-slot:actions>
    </x-mary-drawer>
</div>
