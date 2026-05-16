<x-crm::app-layout>
    <div class="crm-content">
        <x-mary-header title="Laravel CRM {{ ucfirst(__('laravel-crm::lang.updates')) }}" progress-indicator></x-mary-header>
        <x-mary-card shadow>
            @php
                $currentVersion  = \VentureDrake\LaravelCrm\Models\Setting::where('name', 'version')->first()?->value;
                $latestVersion   = \VentureDrake\LaravelCrm\Models\Setting::where('name', 'version_latest')->first()?->value;
                $isLatest        = $currentVersion && $latestVersion && $currentVersion >= $latestVersion;
            @endphp
            <div class="grid gap-y-3">
                @if($currentVersion)
                    <p>{{ ucfirst(__('laravel-crm::lang.current_version')) }}: <strong>{{ $currentVersion }}</strong>
                        @if($isLatest)
                            &mdash; {{ __('laravel-crm::lang.is_the_latest_version') }}
                        @endif
                    </p>
                @else
                    <p>{{ ucfirst(__('laravel-crm::lang.current_version')) }}: &mdash;</p>
                @endif

                @if($currentVersion && $latestVersion && $currentVersion < $latestVersion)
                    <p>{{ ucfirst(__('laravel-crm::lang.updated_version_of_laravel_crm_is_available')) }}</p>
                    <p>{{ ucfirst(__('laravel-crm::lang.you_can_update_from_laravel_crm')) }} {{ $currentVersion }} to {{ $latestVersion }}</p>
                    <p>
                        <a type="button" class="btn btn-primary text-white" href="https://github.com/venturedrake/laravel-crm" target="_blank">{{ ucfirst(__('laravel-crm::lang.upgrade_guide')) }}</a>
                    </p>
                @endif
            </div>
        </x-mary-card>
    </div>
</x-crm::app-layout>
