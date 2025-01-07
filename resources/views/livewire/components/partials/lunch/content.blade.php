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
    <br />
    <span class="badge badge-secondary">{{ $lunch->start_at->format('h:i A') }} on {{ $lunch->start_at->toFormattedDateString() }}</span> to <span class="badge badge-secondary">{{ $lunch->finish_at->format('h:i A') }} on {{ $lunch->finish_at->toFormattedDateString() }}</span>
    @if($lunch->contacts->count() > 0)
        <hr />
        <h6><strong>Guests</strong></h6>
        @foreach($lunch->contacts as $contact)
            <span class="fa fa-user mr-1" aria-hidden="true"></span> <a href="{{ route('laravel-crm.people.show', $contact->entityable) }}">{{ $contact->entityable->name }}</a><br />
        @endforeach
    @endif
    @if($lunch->location)
        <hr />
        <h6><strong>Location</strong></h6>
        {{ $lunch->location }}
    @endif
    @if($lunch->location)
        <hr />
        <h6><strong>Description</strong></h6>
        {{ $lunch->description }}
    @endif
@endif
