<div class="dropdown dropleft close-dropdown">
    <button type="button" class="close dropdown-toggle" data-toggle="dropdown" aria-expanded="false" aria-label="Close">
        <span aria-hidden="true"><span class="fa fa-ellipsis-h" aria-hidden="true"></span></span>
    </button>
    <div class="dropdown-menu">
        <a class="dropdown-item" href="#edit" wire:click="toggleEditMode()">Edit</a>
        <a class="dropdown-item" href="#complete" wire:click="complete()">Complete</a>
        <a class="dropdown-item" href="#delete" wire:click.prevent="delete()">Delete</a>
    </div>
</div>