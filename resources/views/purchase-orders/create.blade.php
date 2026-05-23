<x-crm::app-layout title="{{ ucfirst(__('laravel-crm::lang.purchase_orders')) }}">
    <livewire:crm-purchase-order-create :$fromModelType :$fromModelId />
</x-crm::app-layout>
