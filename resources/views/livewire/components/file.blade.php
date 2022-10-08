<li class="media">
    <div class="card w-100 mb-2">
        <div class="card-body">
            <div class="media-body">
                <a href="#download" wire:click.prevent="download()">{{ $file->name }}</a>@include('laravel-crm::livewire.components.partials.file.actions', ['file' => $file])
                <br /><small>{{ $file->created_at->format('h:i A') }} on {{ $file->created_at->toFormattedDateString() }} | {{ $file->createdByUser->name }} | {{ ($file->filesize / 1000) }} kB</small>
            </div>
        </div>
    </div>
</li>