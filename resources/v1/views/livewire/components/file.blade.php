<li class="media">
    <div class="card w-100 mb-2">
        <div class="card-body">
            <div class="media-body">
                <a href="#download" wire:click.prevent="download()">{{ $file->name }}</a>@include('laravel-crm::livewire.components.partials.file.actions', ['file' => $file])
                @if($showRelated)
                    <p class="pb-0 mb-2">
                        @if($file->filable instanceof \VentureDrake\LaravelCrm\Models\Person)
                            <span class="fa fa-user-circle" aria-hidden="true"></span> <a
                                    href="{{ route('laravel-crm.people.show', $file->filable) }}">{{ $file->filable->name }}</a>
                        @elseif($file->filable instanceof \VentureDrake\LaravelCrm\Models\Organisation)
                            <span class="fa fa-building" aria-hidden="true"></span> <a
                                    href="{{ route('laravel-crm.organisations.show', $file->filable) }}">{{ $file->filable->name }}</a>
                        @endif
                    </p>
                @endif
                <br /><small>{{ $file->created_at->format('h:i A') }} on {{ $file->created_at->toFormattedDateString() }} | {{ $file->createdByUser->name }} | {{ ($file->filesize / 1000) }} kB</small>
            </div>
        </div>
    </div>
</li>