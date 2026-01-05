<x-crm::app-layout>
    <div class="grid lg:grid-cols-10 gap-5">
        <div class="lg:col-span-2">
           @include('laravel-crm::layouts.partials.nav-settings')
        </div>
        <div class="lg:col-span-8">
            <div class="crm-content">
                {{-- HEADER --}}
                <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.general_settings')) }}" class="mb-5" progress-indicator></x-mary-header>

                <x-mary-form wire:submit="save">
                    <div class="grid lg:grid-cols-2 gap-5">
                     
                    </div>
                    <x-slot:actions>
                        <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save_changes')) }}" class="btn-primary text-white" type="submit" spinner="save" />
                    </x-slot:actions>
                </x-mary-form>
            </div>
        </div>
    </div>
</x-crm::app-layout>
