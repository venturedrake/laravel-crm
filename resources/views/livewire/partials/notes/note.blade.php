{{--<form wire:submit.prevent="update">
    @include('laravel-crm::livewire.partials.notes.form-fields', [
        'note' => $note
    ])
    <div class="form-group">
        <button type="submit" class="btn btn-outline-secondary">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
        <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
    </div>
</form>--}}
{!! $note->content !!}
@if($note->noted_at)
    <br />
    <span class="badge badge-secondary">{{ ucfirst(__('laravel-crm::lang.noted_at')) }} {{ $note->noted_at->format('h:i A') }} on {{ $note->noted_at->toFormattedDateString() }}</span>
@endif 