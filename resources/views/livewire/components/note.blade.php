<li class="media">
    <div class="card w-100 mb-2">
        <div class="card-body">
            {{--<img src="..." class="mr-3" alt="...">--}}
            <div class="media-body">
                @if($note->relatedNote)
                    <h5 class="mt-0 mb-1">{{ $note->relatedNote->created_at->diffForHumans() }}
                        - {{ $note->relatedNote->createdByUser->name }} @include('laravel-crm::livewire.components.partials.note.actions', ['note' => $note])</h5>
                    <p class="pb-0 mb-2">
                        @if($note->relatedNote->noteable instanceof \VentureDrake\LaravelCrm\Models\Person)
                            <span class="fa fa-user mr-1" aria-hidden="true"></span> <a
                                    href="{{ route('laravel-crm.people.show', $note->relatedNote->noteable) }}">{{ $note->relatedNote->noteable->name }}</a>
                        @elseif($note->relatedNote->noteable instanceof \VentureDrake\LaravelCrm\Models\Organisation)
                            <span class="fa fa-building mr-1" aria-hidden="true"></span> <a
                                    href="{{ route('laravel-crm.organisations.show', $note->relatedNote->noteable) }}">{{ $note->relatedNote->noteable->name }}</a>
                        @endif
                    </p>
                    @include('laravel-crm::livewire.components.partials.note.content', ['note' => $note->relatedNote])
                @else
                    <h5 class="mt-0 mb-1">{{ $note->created_at->diffForHumans() }}
                        - {{ $note->createdByUser->name }} @include('laravel-crm::livewire.components.partials.note.actions', ['note' => $note])</h5>
                    @include('laravel-crm::livewire.components.partials.note.content', ['note' => $note])
                @endif
            </div>
        </div>
    </div>
    @push('livewire-js')
        <script>
            $(document).ready(function () {
                $(document).on("change", "input[name='noted_at']", function () {
                @this.set('noted_at', $(this).val());
                });
            });
        </script>
    @endpush
</li>

