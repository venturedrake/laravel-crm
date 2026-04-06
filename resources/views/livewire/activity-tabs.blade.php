<div>
    <livewire:crm-note-related :$model :pinned="true" />
    <x-mary-tabs wire:model="activeTab">
        <x-mary-tab name="activity" label="{{ ucfirst(__('laravel-crm::lang.activity')) }}">
            <div>
                <livewire:crm-activity-index :$model />
            </div>
        </x-mary-tab>
        <x-mary-tab name="notes" label="{{ ucfirst(__('laravel-crm::lang.notes')) }}">
            <div>
                <livewire:crm-note-related :$model />
            </div>
        </x-mary-tab>
        <x-mary-tab name="tasks" label="{{ ucfirst(__('laravel-crm::lang.tasks')) }}">
            <div>
                <livewire:crm-task-related :$model />
            </div>
        </x-mary-tab>
        <x-mary-tab name="calls" label="{{ ucfirst(__('laravel-crm::lang.calls')) }}">
            <div>
                <livewire:crm-call-related :$model />
            </div>
        </x-mary-tab>
        <x-mary-tab name="meetings" label="{{ ucfirst(__('laravel-crm::lang.meetings')) }}">
            <div>
                <livewire:crm-meeting-related :$model />
            </div>
        </x-mary-tab>
        <x-mary-tab name="lunches" label="{{ ucfirst(__('laravel-crm::lang.lunches')) }}">
            <div>
                <livewire:crm-lunch-related :$model />
            </div>
        </x-mary-tab>
        <x-mary-tab name="files" label="{{ ucfirst(__('laravel-crm::lang.files')) }}">
            <div>Files</div>
        </x-mary-tab>
    </x-mary-tabs>
</div>
