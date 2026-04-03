<div>
    <livewire:crm-notes-index :$model :pinned="true" />
    <x-mary-tabs wire:model="activeTab">
        <x-mary-tab name="activity" label="{{ ucfirst(__('laravel-crm::lang.activity')) }}">
            <div>Activity</div>
        </x-mary-tab>
        <x-mary-tab name="notes" label="{{ ucfirst(__('laravel-crm::lang.notes')) }}">
            <div>
                <livewire:crm-notes-index :$model />
            </div>
        </x-mary-tab>
        <x-mary-tab name="tasks" label="{{ ucfirst(__('laravel-crm::lang.tasks')) }}">
            <div>Tasks</div>
        </x-mary-tab>
        <x-mary-tab name="calls" label="{{ ucfirst(__('laravel-crm::lang.calls')) }}">
            <div>Calls</div>
        </x-mary-tab>
        <x-mary-tab name="meetings" label="{{ ucfirst(__('laravel-crm::lang.meetings')) }}">
            <div>Meetings</div>
        </x-mary-tab>
        <x-mary-tab name="lunches" label="{{ ucfirst(__('laravel-crm::lang.lunches')) }}">
            <div>Lunches</div>
        </x-mary-tab>
        <x-mary-tab name="files" label="{{ ucfirst(__('laravel-crm::lang.files')) }}">
            <div>Files</div>
        </x-mary-tab>
    </x-mary-tabs>
</div>
