<div class="grid gap-5">
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.add_task')) }}" separator>
        <x-mary-form wire:submit="save">
            <div class="grid gap-3" wire:key="details">
                <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.task')) }}" />
                <x-mary-datetime wire:model="due_at" label="{{ ucfirst(__('laravel-crm::lang.whens_it_due')) }}" type="datetime-local"  />
                <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.further_details')) }}" rows="5" />
                <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.who_requested_the_task')) }}" wire:model="user_owner_id" :options="$users" />
                <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.who_is_responsible')) }}" wire:model="user_assigned_id" :options="$users" />
            </div>
            <x-slot:actions>
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-card>

    @if(count($tasks) > 0)
        @foreach($tasks as $task)
            @livewire('crm-task-item', ['task' => $task, 'related' => $data[$task->id]['related']], key('task-item-'.$task->id))
        @endforeach
    @endif
</div>

