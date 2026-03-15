<div class="crm-content">
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.invoices')) }}" progress-indicator>
        {{--  SEARCH --}}
        <x-slot:middle class="justify-end!">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.search_invoices')) }}..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:middle>

        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-mary-button label="Filters"
                           icon="o-funnel"
                           :badge="$filterCount ?? 0"
                           badge-classes="font-mono badge-primary badge-soft"
                           @click="$wire.showFilters = true"
                           responsive />

           {{-- <x-crm-index-toggle :layout="$layout" model="invoices"/>--}}

            @can('create crm invoices')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_invoice')) }}" link="{{ url(route('laravel-crm.invoices.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
            @endcan    
        </x-slot:actions>
    </x-mary-header>

    {{-- TABLE --}}
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$invoices" link="/invoices/{id}" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('cell_pipeline_stage', $invoice)
                @if($invoice->pipelineStage)
                    <x-mary-badge :value="$invoice->pipelineStage->name" class="badge badge-neutral text-white" />
                @endif
            @endscope
            @scope('cell_overdue_by', $invoice)
                @if(! $invoice->fully_paid_at && abs($invoice->due_date->diffinDays()) > 0 && $invoice->due_date < \Carbon\Carbon::now()->timezone($this->timezone ))
                    {{ $invoice->due_date->diffForHumans() }}
                @endif
            @endscope
            @scope('cell_sent', $invoice)
                @if($invoice->sent != 1)
                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.sent')) }}" class="badge badge-success text-white" />
                @endif
            @endscope
            @scope('actions', $invoice)
                <div class="flex gap-1 justify-end">
                    <livewire:crm-invoice-send :key="'invoice-send-'.$invoice->id" :$invoice />
                    @if(! $invoice->xeroInvoice)
                        @if(! $invoice->fully_paid_at)
                            <livewire:crm-invoice-pay :key="'invoice-pay-'.$invoice->id" :$invoice />
                        @endif
                    @endif
                    @can('view crm invoices')
                        <x-mary-button icon="o-arrow-down-tray" link="{{ url(route('laravel-crm.invoices.download', $invoice)) }}" class="btn-sm btn-square btn-outline" />
                        <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.invoices.show', $invoice)) }}" class="btn-sm btn-square btn-outline" />
                    @endcan
                    @if(! $invoice->xeroInvoice)
                        @if($invoice->amount_paid <= 0)
                            @can('edit crm invoices')
                                <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.invoices.edit', $invoice)) }}" class="btn-sm btn-square btn-outline" />
                            @endcan
                            @can('delete crm invoices')
                                <x-mary-button onclick="modalDeleteInvoice{{ $invoice->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                                <x-crm-delete-confirm model="invoice" id="{{ $invoice->id }}" />
                            @endcan
                        @endif
                    @endif
                    @if($invoice->xeroInvoice)
                        <img src="/vendor/laravel-crm/img/xero-icon.png" height="30" />
                    @endif
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
