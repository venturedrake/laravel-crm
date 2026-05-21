<x-crm::app-layout title="{{ ucfirst(__('laravel-crm::lang.orders')) }}">
    <livewire:crm-order-create :$fromModelType :$fromModelId />
</x-crm::app-layout>
