<div class="dropdown dropleft close-dropdown">
    <button type="button" class="close dropdown-toggle" data-toggle="dropdown" aria-expanded="false" aria-label="Close">
        <span aria-hidden="true"><span class="fa fa-ellipsis-h" aria-hidden="true"></span></span>
    </button>
    <div class="dropdown-menu">
        <a class="dropdown-item" href="#">Edit</a>
        <a class="dropdown-item" href="#" wire:click.prevent="delete({{$note->id}})">Delete</a>
    </div>
</div>