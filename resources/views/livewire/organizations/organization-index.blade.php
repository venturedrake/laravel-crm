<div class="crm-content">
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.organizations')) }}" progress-indicator>
        {{--  SEARCH --}}
        <x-slot:middle class="justify-end!">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.search_organizations')) }}..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:middle>

        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-mary-button label="Filters"
                           icon="o-funnel"
                           :badge="$filterCount ?? 0"
                           badge-classes="font-mono badge-primary badge-soft"
                           @click="$wire.showFilters = true"
                           responsive />

           {{-- <x-crm-index-toggle :layout="$layout" model="organizations"/>--}}

            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_organization')) }}" link="{{ url(route('laravel-crm.organizations.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
        </x-slot:actions>
    </x-mary-header>

    {{-- TABLE --}}
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$organizations" link="/organizations/{id}" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('cell_xeroContact', $organization)
                @if($organization->xeroContact)
                    <img src="/vendor/laravel-crm/img/xero-icon.png" height="20"/>
                @endif
            @endscope
            @scope('cell_labels', $organization)
                @foreach($organization->labels as $label)
                    <x-mary-badge value="{{ $label->name }}" class="text-white" style="border-color: #{{ $label->hex }}; background-color: #{{ $label->hex }}" />
                @endforeach 
            @endscope
            @scope('cell_open_deals', $organization)
                {{ $organization->deals->whereNull('closed_at')->count() }}
            @endscope
            @scope('cell_lost_deals', $organization)
                {{ $organization->deals->where('closed_status', 'lost')->count() }}
            @endscope
            @scope('cell_won_deals', $organization)
                {{ $organization->deals->where('closed_status', 'won')->count() }}
            @endscope
            @scope('actions', $organization)
            <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.organizations.show', $organization)) }}" class="btn-sm btn-square btn-outline" />
            <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.organizations.edit', $organization)) }}" class="btn-sm btn-square btn-outline" />
            <x-mary-button onclick="modalDeleteOrganization{{ $organization->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
            <x-crm-delete-confirm model="organization" id="{{ $organization->id }}" />
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
