@if($editMode)
    <form wire:submit.prevent="update">
        @include('laravel-crm::livewire.components.partials.lunch.form-fields')
        <div class="form-group">
            <button type="button" class="btn btn-outline-secondary" wire:click="toggleEditMode()">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
            <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
        </div>
    </form>
@else
    {!! $lunch->description !!}
    @if($lunch->start_at)
        <br />
        <span class="badge badge-secondary">{{ $lunch->start_at->format('h:i A') }} on {{ $lunch->start_at->toFormattedDateString() }}</span>
    @endif
@endif
