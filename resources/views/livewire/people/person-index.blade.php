<div class="crm-content">
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.people')) }}" progress-indicator>
        {{--  SEARCH --}}
        <x-slot:middle class="justify-end!">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.search_people')) }}..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:middle>

        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-mary-button label="Filters"
                           icon="o-funnel"
                           :badge="$filterCount ?? 0"
                           badge-classes="font-mono badge-primary badge-soft"
                           @click="$wire.showFilters = true"
                           responsive />

           {{-- <x-crm-index-toggle :layout="$layout" model="people"/>--}}
            @can('create crm people')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.import_people')) }}" link="{{ url(route('laravel-crm.people.import')) }}" icon="o-arrow-up-tray" class="btn-outline" responsive />
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_person')) }}" link="{{ url(route('laravel-crm.people.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    {{-- TABLE --}}
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$people" :link="route('laravel-crm.people.show', ['person' => '[id]'])" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('cell_labels', $person)
                @foreach($person->labels as $label)
                    <x-mary-badge :value="$label->name" class="text-white" :style="'border-color: #'.$label->hex.'; background-color: #'.$label->hex" />
                @endforeach
            @endscope
            @scope('cell_email', $person)
                {{ $person->getPrimaryEmail()->address ?? null }}
            @endscope
            @scope('cell_phone', $person)
                {{ $person->getPrimaryPhone()->number ?? null }}
            @endscope
            @scope('cell_open_deals', $person)
                {{ $person->deals->whereNull('closed_at')->count() }}
            @endscope
            @scope('cell_lost_deals', $person)
                {{ $person->deals->where('closed_status', 'lost')->count() }}
            @endscope
            @scope('cell_won_deals', $person)
                {{ $person->deals->where('closed_status', 'won')->count() }}
            @endscope
            @scope('actions', $person)
                @hasleadsenabled
                    @can('create crm leads')
                        <a href="{{ url(route('laravel-crm.leads.create',  ['model' => 'person', 'id' => $person->id])) }}"><button class="btn btn-sm btn-outline btn-rectangle"><x-mary-icon name="o-arrow-right" /><x-mary-icon name="fas.crosshairs" /></button></a>
                    @endcan
                @endhasleadsenabled
                @hasdealsenabled
                    @can('create crm deals')
                        <a href="{{ url(route('laravel-crm.deals.create',  ['model' => 'person', 'id' => $person->id])) }}"><button class="btn btn-sm btn-outline btn-rectangle"><x-mary-icon name="o-arrow-right" /><x-mary-icon name="fas.dollar-sign" /></button></a>
                    @endcan
                @endhasdealsenabled
                @hasquotesenabled
                    @can('create crm quotes')
                        <a href="{{ url(route('laravel-crm.quotes.create',  ['model' => 'person', 'id' => $person->id])) }}"><button class="btn btn-sm btn-outline btn-rectangle"><x-mary-icon name="o-arrow-right" /><x-mary-icon name="fas.file-lines" /></button></a>
                    @endcan
                @endhasquotesenabled
                @hasordersenabled
                    @can('create crm orders')
                        <a href="{{ url(route('laravel-crm.orders.create',  ['model' => 'person', 'id' => $person->id])) }}"><button class="btn btn-sm btn-outline btn-rectangle"><x-mary-icon name="o-arrow-right" /><x-mary-icon name="fas.shopping-cart" /></button></a>
                    @endcan
                @endhasordersenabled
                @can('view crm people')
                    <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.people.show', $person)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('edit crm people')
                    <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.people.edit', $person)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('delete crm people')
                    <x-mary-button onclick="modalDeletePerson{{ $person->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                    <x-crm-delete-confirm model="person" id="{{ $person->id }}" />
                @endcan
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
