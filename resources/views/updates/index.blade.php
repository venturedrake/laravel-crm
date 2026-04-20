<x-crm::app-layout>
    <div class="crm-content">
        <x-mary-header title="Laravel CRM {{ ucfirst(__('laravel-crm::lang.updates')) }}" progress-indicator></x-mary-header>
        <x-mary-card shadow>
            <div class="grid gap-y-3">
                <p>Your current version {{ ucfirst(__('laravel-crm::lang.current_version')) }} {{ \VentureDrake\LaravelCrm\Models\Setting::where('name','version')->first()->value }} {{ (\VentureDrake\LaravelCrm\Models\Setting::where('name','version')->first()->value == \VentureDrake\LaravelCrm\Models\Setting::where('name','version_latest')->first()->value) ? __('laravel-crm::lang.is_the_latest_version') : null }} is latest. </p>
                            @if(\VentureDrake\LaravelCrm\Models\Setting::where('name','version')->first()->value < \VentureDrake\LaravelCrm\Models\Setting::where('name','version_latest')->first()->value)
                <p>{{ ucfirst(__('laravel-crm::lang.updated_version_of_laravel_crm_is_available')) }}</p>
                <p class="card-text">{{ ucfirst(__('laravel-crm::lang.you_can_update_from_laravel_crm')) }} {{ \VentureDrake\LaravelCrm\Models\Setting::where('name','version')->first()->value }} to {{ \VentureDrake\LaravelCrm\Models\Setting::where('name','version_latest')->first()->value }}</p>
                <p>
                    <a type="button" class="btn btn-primary text-white" href="https://github.com/venturedrake/laravel-crm" target="_blank">{{ ucfirst(__('laravel-crm::lang.upgrade_guide')) }}</a>
                </p>
            @endif
            </div>
        </x-mary-card>
    </div>
</x-crm::app-layout>
