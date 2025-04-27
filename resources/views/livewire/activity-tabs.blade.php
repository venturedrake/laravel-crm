<div>
    {{--<div class="tabs tabs-lift tabs-md">
        <a role="tab" class="tab tab-active">{{ ucfirst(__('laravel-crm::lang.activity')) }}</a>
        <div class="tab-content bg-base-100 border-base-300 p-5 block">
            ACTIVITY
        </div>
        <a role="tab" class="tab ">{{ ucfirst(__('laravel-crm::lang.notes')) }}</a>
        <div class="tab-content bg-base-100 border-base-300 p-5">
            NOTES
        </div>
        <a role="tab" class="tab">{{ ucfirst(__('laravel-crm::lang.tasks')) }}</a>
        <div class="tab-content bg-base-100 border-base-300 p-5">
            TASKS
        </div>
        <a role="tab" class="tab">{{ ucfirst(__('laravel-crm::lang.calls')) }}</a>
        <div class="tab-content bg-base-100 border-base-300 p-5">
            CALLS
        </div>
        <a role="tab" class="tab">{{ ucfirst(__('laravel-crm::lang.meetings')) }}</a>
        <div class="tab-content bg-base-100 border-base-300 p-5">
            MEETINGS
        </div>
        <a role="tab" class="tab">{{ ucfirst(__('laravel-crm::lang.lunches')) }}</a>
        <div class="tab-content bg-base-100 border-base-300 p-5">
            LUNCHES
        </div>
        <a role="tab" class="tab">{{ ucfirst(__('laravel-crm::lang.files')) }}</a>
        <div class="tab-content bg-base-100 border-base-300 p-5">
            FILES
        </div>
    </div>--}}
    <x-mary-tabs wire:model="activeTab">
        <x-mary-tab name="activity" label="{{ ucfirst(__('laravel-crm::lang.activity')) }}">
            <div>Activity</div>
        </x-mary-tab>
        <x-mary-tab name="notes" label="{{ ucfirst(__('laravel-crm::lang.notes')) }}">
            <div>Notes</div>
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
