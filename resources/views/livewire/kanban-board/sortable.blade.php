@push('livewire-js')
    <script>
    window.onload = () => {
        @foreach($stages as $stage)
            Sortable.create(document.getElementById('{{ $stage['stageRecordsId'] }}'), {
                group: '{{ $sortableBetweenStages ? $stage['group'] : $stage['id'] }}',
                animation: 0,
                ghostClass: 'bg-light',

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

                    const fromStageId = evt.from.dataset.stageId;
                    const fromOrderedIds = [].slice.call(evt.from.children).map(child => child.id);

                    if (sameContainer) {
                        @this.call('onStageSorted', recordId, fromStageId, fromOrderedIds);
                        return;
                    }

                    const toStageId = evt.to.dataset.stageId;
                    const toOrderedIds = [].slice.call(evt.to.children).map(child => child.id);

                    @this.call('onStageChanged', recordId, toStageId, fromOrderedIds, toOrderedIds);
                },
            });
        @endforeach
    }
</script>
@endpush    
