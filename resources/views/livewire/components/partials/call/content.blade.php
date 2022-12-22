@if($editMode)
    <form wire:submit.prevent="update">
        @include('laravel-crm::livewire.components.partials.call.form-fields')
        <div class="form-group">
            <button type="button" class="btn btn-outline-secondary" wire:click="toggleEditMode()">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
            <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
        </div>
    </form>
@else
    {!! $call->description !!}
    @if($call->start_at)
        <br />
        <span class="badge badge-secondary">{{ $call->start_at->format('h:i A') }} on {{ $call->start_at->toFormattedDateString() }}</span>
    @endif
@endif
