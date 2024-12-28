@if($editMode)
    <form wire:submit.prevent="update">
        @include('laravel-crm::livewire.components.partials.note.form-fields')
        <div class="form-group">
            <button type="button" class="btn btn-outline-secondary" wire:click="toggleEditMode()">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
            <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
        </div>
    </form>
@else    
    {!! $note->content !!}
    @if($note->noted_at)
        <br />
        <span class="badge badge-secondary">{{ ucfirst(__('laravel-crm::lang.noted_at')) }} {{ $note->noted_at->format('h:i A') }} on {{ $note->noted_at->toFormattedDateString() }}</span>
    @endif
@endif  
    