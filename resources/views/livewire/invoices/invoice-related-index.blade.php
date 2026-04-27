<div>
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$this->invoices" :link="route('laravel-crm.invoices.show', ['invoice' => '[id]'])" class="whitespace-nowrap">
            @scope('cell_pipeline_stage', $invoice)
                @if($invoice->pipelineStage)
                    <x-mary-badge :value="$invoice->pipelineStage->name" class="badge badge-neutral text-white" />
                @endif
            @endscope
            @scope('cell_order', $invoice)
                @if($invoice->order)
                    <a href="{{ route('laravel-crm.orders.show', $invoice->order) }}" class="link link-hover link-primary">{{ $invoice->order->order_id }}</a>
                @endif
            @endscope
            @scope('cell_overdue_by', $invoice)
                @if(! $invoice->fully_paid_at && $invoice->due_date && abs($invoice->due_date->diffInDays()) > 0 && $invoice->due_date < \Carbon\Carbon::now()->timezone($this->timezone))
                    {{ $invoice->due_date->diffForHumans() }}
                @endif
            @endscope
            @scope('cell_sent', $invoice)
                @if($invoice->sent == 1)
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
</div>

