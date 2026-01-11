<x-crm::app-layout>
    <div class="grid lg:grid-cols-10 gap-5">
        <div class="lg:col-span-2">
           @include('laravel-crm::layouts.partials.nav-settings')
        </div>
        <div class="lg:col-span-8">
            <livewire:crm-settings-edit />
        </div>
    </div>
</x-crm::app-layout>
