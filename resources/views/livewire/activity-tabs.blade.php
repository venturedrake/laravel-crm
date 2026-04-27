<div>
    <livewire:crm-note-related :$model :pinned="true" />
    <x-mary-tabs wire:model="activeTab">
        <x-mary-tab name="activity" label="{{ ucfirst(__('laravel-crm::lang.activity')) }}">
            <div>
                <livewire:crm-activity-index :$model />
            </div>
        </x-mary-tab>
        @if($model->orders)
            <x-mary-tab name="orders" label="{{ ucfirst(__('laravel-crm::lang.orders')) }}">
                <div>
                    <livewire:crm-order-related-index :$model />
                </div>
            </x-mary-tab>
        @endif
        @if($model->invoices)
            <x-mary-tab name="invoices" label="{{ ucfirst(__('laravel-crm::lang.invoices')) }}">
                <div>
                    <livewire:crm-invoice-related-index :$model />
                </div>
            </x-mary-tab>
        @endif
        @if($model->deliveries)
            <x-mary-tab name="deliveries" label="{{ ucfirst(__('laravel-crm::lang.deliveries')) }}">
                <div>
                    <livewire:crm-delivery-related-index :$model />
                </div>
            </x-mary-tab>
        @endif
        @if($model->purchaseOrders)
            <x-mary-tab name="purchase-order" label="{{ ucwords(__('laravel-crm::lang.purchase_orders')) }}">
                <div>
                    <livewire:crm-purchase-order-related-index :$model />
                </div>
            </x-mary-tab>
        @endif
        <x-mary-tab name="notes" label="{{ ucfirst(__('laravel-crm::lang.notes')) }}">
            <div>
                <livewire:crm-note-related :$model />
            </div>
        </x-mary-tab>
        <x-mary-tab name="tasks" label="{{ ucfirst(__('laravel-crm::lang.tasks')) }}">
            <div>
                <livewire:crm-task-related :$model />
            </div>
        </x-mary-tab>
        <x-mary-tab name="calls" label="{{ ucfirst(__('laravel-crm::lang.calls')) }}">
            <div>
                <livewire:crm-call-related :$model />
            </div>
        </x-mary-tab>
        <x-mary-tab name="meetings" label="{{ ucfirst(__('laravel-crm::lang.meetings')) }}">
            <div>
                <livewire:crm-meeting-related :$model />
            </div>
        </x-mary-tab>
        <x-mary-tab name="lunches" label="{{ ucfirst(__('laravel-crm::lang.lunches')) }}">
            <div>
                <livewire:crm-lunch-related :$model />
            </div>
        </x-mary-tab>
        <x-mary-tab name="files" label="{{ ucfirst(__('laravel-crm::lang.files')) }}">
            <div>
                <livewire:crm-file-related :$model />
            </div>
        </x-mary-tab>
    </x-mary-tabs>
</div>
