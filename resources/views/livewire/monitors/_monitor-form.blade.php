<div class="grid lg:grid-cols-2 gap-5">
    <x-mary-input wire:model="url" label="{{ ucfirst(__('laravel-crm::lang.website_url')) }}" placeholder="example.com" class="lg:col-span-2">
        <x-slot:prepend>
            <x-mary-select wire:model="type" :options="[['id' => 'https', 'name' => 'https'], ['id' => 'http', 'name' => 'http']]" class="join-item" />
        </x-slot:prepend>
    </x-mary-input>
    <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.friendly_name')) }}" hint="{{ __('laravel-crm::lang.friendly_name_hint') }}" />
    <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="3" class="lg:col-span-2" />
    <x-mary-select wire:model="method" :options="$this->methodOptions()" label="{{ ucfirst(__('laravel-crm::lang.method')) }}" hint="{{ __('laravel-crm::lang.method_hint') }}" />
    <x-mary-input wire:model="expected_status_code" type="number" label="{{ ucfirst(__('laravel-crm::lang.expected_status_code')) }}" />
    <x-mary-input wire:model="interval" type="number" min="1" label="{{ ucfirst(__('laravel-crm::lang.run_check_every')) }}" suffix="{{ __('laravel-crm::lang.minutes') }}" />
    <x-mary-input wire:model="downtime_minutes_before_alert" type="number" min="1" label="{{ ucfirst(__('laravel-crm::lang.minutes_downtime_before_notification')) }}" suffix="{{ __('laravel-crm::lang.minutes') }}" hint="{{ __('laravel-crm::lang.downtime_before_alert_hint') }}" />
    <x-mary-input wire:model="perf_threshold_ms" type="number" min="1" label="{{ ucfirst(__('laravel-crm::lang.monitor_performance_threshold')) }}" suffix="{{ __('laravel-crm::lang.ms') }}" hint="{{ __('laravel-crm::lang.threshold_hint') }}" />
    <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.owner')) }}" wire:model="user_owner_id" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\usersOptions(false)" />
    <x-mary-toggle wire:model="is_active" label="{{ ucfirst(__('laravel-crm::lang.active')) }}" />
</div>
