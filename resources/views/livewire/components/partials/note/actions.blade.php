<div class="dropdown dropleft close-dropdown">
    <button type="button" class="close dropdown-toggle" data-toggle="dropdown" aria-expanded="false" aria-label="Close">
        <span aria-hidden="true"><span class="fa fa-ellipsis-h" aria-hidden="true"></span></span>
    </button>
    <div class="dropdown-menu">
        <a class="dropdown-item" href="#edit" wire:click="toggleEditMode()">Edit</a>
        @if($note->pinned == 1)
            <a class="dropdown-item" href="#pin" wire:click.prevent="unpin()">Unpin this note</a>
        @else
            <a class="dropdown-item" href="#pin" wire:click.prevent="pin()">Pin this note</a>
        @endif
        <a class="dropdown-item" href="#delete" wire:click.prevent="delete()">Delete</a>
    </div>
</div>