<x-crm::app-layout title="{{ ucfirst(__('laravel-crm::lang.leads')) }}">
    <livewire:crm-lead-create :$fromModelType :$fromModelId />
</x-crm::app-layout>
