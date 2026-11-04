@script
<script>
    // Separate from sortable.blade.php, which only loads for users who can edit.
    const column = stage => $wire.$el.querySelector(`[data-board-stage="${stage}"]`);

    $wire.on('board-column-loaded', ({ stage, html, hasMore }) => {
        const target = column(stage);

        if (! target) {
            return;
        }

        const records = target.querySelector('[data-stage-id]');
        const page = document.createElement('template');

        page.innerHTML = html;

        // A card can already be on the board if someone moved it since this page loaded.
        [...page.content.children]
            .filter(el => ! $wire.$el.querySelector(`[id="${el.id}"]`))
            .forEach(el => records.appendChild(el));

        target.querySelector('[data-board-more]').classList.toggle('hidden', ! hasMore);
    });

    $wire.on('board-columns-updated', ({ columns }) => {
        columns.forEach(({ stage, count, totals, hasMore }) => {
            const target = column(stage);

            if (! target) {
                return;
            }

            target.querySelector('[data-board-count]').textContent = count;

            target.querySelector('[data-board-totals]').replaceChildren(...totals.map(total => {
                const line = document.createElement('div');

                line.textContent = total;

                return line;
            }));

            target.querySelector('[data-board-more]').classList.toggle('hidden', ! hasMore);
        });
    });
</script>
@endscript
