{{--
    A card's menu. The board's overflow-auto wrapper clips anything inside it, so the
    list moves to <body> and x-anchor places it, flipping it up when there's no room
    below. It only exists while open, so a board of cards doesn't keep one in the page
    and an anchor watching the scroll for each. Everything that closes it sits on the
    list for the same reason. Only a scroll that moves the trigger closes it: the search
    box scrolls its own text back when a click on the menu blurs it.

    Back and Forward save the page's HTML straight after livewire:navigating, too soon
    for x-if to react to open. A saved teleport template would come back as a list x-if
    doesn't own and nothing could close, so that handler removes it itself.
--}}
<div data-board-menu x-data="{ open: false }">
    <button type="button" class="btn btn-xs btn-square" x-ref="trigger" x-on:click="open = ! open" x-bind:aria-expanded="open" aria-haspopup="menu">
        <x-mary-icon name="o-chevron-down" />
    </button>

    <template x-if="open">
        <template x-teleport="body" x-ref="teleport">
            <ul class="menu absolute z-50 p-2 shadow border-[length:var(--border)] border-base-content/10 bg-base-100 rounded-box w-auto min-w-max max-h-96 overflow-y-auto"
                data-board-menu-items
                x-anchor.bottom-end="$refs.trigger"
                x-on:click="open = false"
                x-on:click.outside="if (! $refs.trigger.contains($event.target)) open = false"
                x-on:keydown.escape.window="open = false"
                x-on:scroll.window.capture="if ($event.target.contains($refs.trigger)) open = false"
                x-on:resize.window="open = false"
                x-on:dragstart.window="open = false"
                x-on:livewire:navigating.window="$refs.teleport.remove(); open = false">
                <div>
                    {{ $slot }}
                </div>
            </ul>
        </template>
    </template>
</div>
