<x-crm::app-layout title="{{ ucfirst(__('laravel-crm::lang.invoices')) }}">
    <livewire:crm-invoice-create :$fromModelType :$fromModelId />
</x-crm::app-layout>
