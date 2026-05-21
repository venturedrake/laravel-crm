<x-crm::app-layout title="{{ ucfirst(__('laravel-crm::lang.orders')) }}">
    <livewire:crm-order-show :order="$order" />
</x-crm::app-layout>
