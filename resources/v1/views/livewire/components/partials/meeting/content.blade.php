@if($editMode)
    <form wire:submit.prevent="update">
        @include('laravel-crm::livewire.components.partials.meeting.form-fields')
        <div class="form-group">
            <button type="button" class="btn btn-outline-secondary" wire:click="toggleEditMode()">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
            <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
        </div>
    </form>
@else
    {!! $meeting->description !!}
    <br />
    <span class="badge badge-secondary">{{ $meeting->start_at->format('h:i A') }} on {{ $meeting->start_at->toFormattedDateString() }}</span> to <span class="badge badge-secondary">{{ $meeting->finish_at->format('h:i A') }} on {{ $meeting->finish_at->toFormattedDateString() }}</span>
    @if($meeting->contacts->count() > 0)
        <hr />
        <h6><strong>Guests</strong></h6>
        @foreach($meeting->contacts as $contact)
            <span class="fa fa-user mr-1" aria-hidden="true"></span> <a href="{{ route('laravel-crm.people.show', $contact->entityable) }}">{{ $contact->entityable->name }}</a><br />
        @endforeach
    @endif
    @if($meeting->location)
        <hr />
        <h6><strong>Location</strong></h6>
        {{ $meeting->location }}
    @endif
    @if($meeting->location)
        <hr />
        <h6><strong>Description</strong></h6>
        {{ $meeting->description }}
    @endif
@endif
