@if($editMode)
    <form wire:submit.prevent="update">
        @include('laravel-crm::livewire.components.partials.task.form-fields')
        <div class="form-group">
            <button type="button" class="btn btn-outline-secondary" wire:click="toggleEditMode()">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
            <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
        </div>
    </form>
@else
    {!! $task->description !!}
    @if($task->due_at)
        <br />
        @include('laravel-crm::livewire.components.partials.task.status') <span class="badge badge-secondary">{{ ucfirst(__('laravel-crm::lang.due')) }} {{ $task->due_at->format('h:i A') }} on {{ $task->due_at->toFormattedDateString() }}</span>
    @endif
@endif
