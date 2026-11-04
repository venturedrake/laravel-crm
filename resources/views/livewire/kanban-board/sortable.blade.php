@script
<script>
    $wire.on('board-loaded', () => {
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

            setData: function (dataTransfer, dragEl) {
                dataTransfer.setData('id', dragEl.id);
            },

            onEnd: function (evt) {
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

                if (sameContainer) {
                    @this.call('onStageSorted', fromOrderedIds);
                    return;
                }

                const toStageId = evt.to.dataset.stageId;
                const toOrderedIds = orderedIds(evt.to);

                @this.call('onStageChanged', recordId, toStageId, fromOrderedIds, toOrderedIds);
            },
        });
        @php } @endphp
    });
</script>
@endscript
