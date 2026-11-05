@script
<script>
    let stale = false;
    let dragging = false;

    // Livewire 3 never settles a failed call's promise, so the hook is what sees it on both.
    $wire.$hook('request', ({ payload, fail }) => {
        const drag = payload.includes('onStage');
        const refresh = payload.includes('refreshBoard');

        fail(({ status, preventDefault }) => {
            const error = document.getElementById('livewire-error');

            // Livewire 3 stops sending anything for good if a dialog opens over another,
            // whichever request it came from. Only an open modal can stand in for this
            // error: a restored panel can't be reopened, so it goes for Livewire's new one.
            if (error?.matches(':modal')) {
                preventDefault();
            } else {
                error?.remove();
            }

            if (! drag && ! refresh) {
                return;
            }

            stale = true;

            // A 419's refresh would send the same expired token, so it could only fail too.
            // This closure outlives the board if wire:navigate removed it mid-request.
            if (drag && status !== 419 && ! dragging && $wire.$el.isConnected) {
                $wire.refreshBoard().catch(() => {});
            }
        });
    });

    $wire.on('board-loaded', () => {
        let lastRender;

        stale = false;

        // board-loaded fires on every render, and a second instance would double the drag.
        const create = (id, options) => {
            const el = document.getElementById(id);

            if (el && ! Sortable.get(el)) {
                Sortable.create(el, options);
            }
        };

        @php foreach($stages as $stage){ @endphp
        create('{{ $stage['stageRecordsId'] }}', {
            group: '{{ $sortableBetweenStages ? $stage['group'] : $stage['id'] }}',
            draggable: '[data-record-id]',
            animation: 0,
            ghostClass: 'bg-primary',

            onStart: function () {
                dragging = true;
                lastRender = $wire.$get('renderCount');
            },

            setData: function (dataTransfer, dragEl) {
                dataTransfer.setData('id', dragEl.id);
            },

            onEnd: function (evt) {
                dragging = false;

                const sameContainer = evt.from === evt.to;
                const orderChanged = evt.oldIndex !== evt.newIndex;

                if (sameContainer && !orderChanged) {
                    return;
                }

                const recordId = evt.item.id;

                // Stage containers also hold non-record siblings (delete dialogs), so only
                // collect the ids of the record cards themselves.
                const orderedIds = el => [].slice.call(el.children)
                    .filter(child => child.dataset.recordId)
                    .map(child => child.dataset.recordId);

                const fromStageId = evt.from.dataset.stageId;
                const fromOrderedIds = orderedIds(evt.from);

                // A failed drag left the board wrong, so this one must not skip the render.
                const rendered = stale ? -1 : lastRender;

                if (sameContainer) {
                    @this.call('onStageSorted', fromOrderedIds, fromStageId, rendered);
                    return;
                }

                const toStageId = evt.to.dataset.stageId;
                const toOrderedIds = orderedIds(evt.to);

                @this.call('onStageChanged', recordId, toStageId, fromOrderedIds, toOrderedIds, fromStageId, rendered);
            },
        });
        @php } @endphp
    });
</script>
@endscript
