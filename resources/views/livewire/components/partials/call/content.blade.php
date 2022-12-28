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
    <br />
    <span class="badge badge-secondary">{{ $call->start_at->format('h:i A') }} on {{ $call->start_at->toFormattedDateString() }}</span> to <span class="badge badge-secondary">{{ $call->finish_at->format('h:i A') }} on {{ $call->finish_at->toFormattedDateString() }}</span>
    @if($call->contacts->count() > 0)
        <hr />
        <h6><strong>Guests</strong></h6>
        @foreach($call->contacts as $contact)
            <span class="fa fa-user mr-1" aria-hidden="true"></span> <a href="{{ route('laravel-crm.people.show', $contact->entityable) }}">{{ $contact->entityable->name }}</a><br />
        @endforeach
    @endif
    @if($call->location)
        <hr />
        <h6><strong>Location</strong></h6>
        {{ $call->location }}
    @endif
    @if($call->location)
        <hr />
        <h6><strong>Description</strong></h6>
        {{ $call->description }}
    @endif
@endif
