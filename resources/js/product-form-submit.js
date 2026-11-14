// Livewire 3 does not reject action promises on every request failure. Track
// requests through the public hook so Save can recover in both major versions.
const requests = new Set();
const submissions = new Set();
const clone = value => JSON.parse(JSON.stringify(value));

function installRequestHook() {
    window.Livewire.hook('request', ({ payload, succeed, fail }) => {
        const body = typeof payload === 'string' ? JSON.parse(payload) : payload;
        const ids = (body?.components ?? []).map(component => JSON.parse(component.snapshot).memo.id);
        let finish;
        const request = { ids, done: new Promise(resolve => { finish = resolve; }) };
        requests.add(request);
        const complete = () => { requests.delete(request); finish(); };
        succeed(complete);
        fail(({ preventDefault }) => {
            for (const submission of submissions) {
                if (ids.some(id => submission.ids.includes(id))) {
                    preventDefault();
                    submission.reject(new Error('Product form request failed'));
                }
            }
            complete();
        });
    });
}

function captureRows(editor, child) {
    const rows = clone(child.$get('products'));
    // Read native controls too: a debounce or money mask may not yet have
    // propagated the visible value into Livewire's reactive properties.
    for (const input of editor.querySelectorAll('input, textarea, select')) {
        const binding = [...input.attributes].find(attribute => attribute.name.startsWith('wire:model'));
        const match = binding?.value.match(/^products\.(\d+)\.([a-z_]+)$/);
        if (match && rows[match[1]] && !input.readOnly && input.type !== 'hidden') {
            rows[match[1]][match[2]] = input.value;
        }
    }
    return rows;
}

function installForm() {
    window.Alpine.data('crmProductForm', () => ({
        busy: false,
        failed: false,
        async submit() {
            if (this.busy) return;
            this.busy = true;
            this.failed = false;
            let child;
            let rows;
            let submission;
            try {
                const editor = this.$el.querySelector('[data-crm-product-editor]');
                if (!editor) throw new Error('Product editor missing');
                child = window.Livewire.find(editor.closest('[wire\\:id]').getAttribute('wire:id'));
                const parent = this.$wire;
                // Nothing asynchronous may precede this snapshot.
                rows = captureRows(editor, child);
                const failure = new Promise((resolve, reject) => {
                    submission = { ids: [child.$id, parent.$id], reject };
                });
                submissions.add(submission);
                const wait = action => Promise.race([action, failure]);
                // Finish an older child request before preparing the captured rows.
                // This also protects retry values from a late child morph.
                while ([...requests].some(request => request.ids.includes(child.$id))) {
                    await wait(Promise.all([...requests].filter(request => request.ids.includes(child.$id)).map(request => request.done)));
                }
                const prepared = await wait(child.$call('prepareForSave', rows));
                if (!prepared || !Array.isArray(prepared.products)) throw new Error('Product preparation failed');
                rows = prepared.products;
                await wait(parent.$call('saveWithProducts', prepared));
            } catch (error) {
                this.failed = true;
                if (child && rows) {
                    // Restore the captured inputs locally; never save a cached
                    // parent array when preparation or transport has failed.
                    await child.$set('products', rows, false);
                }
            } finally {
                if (submission) submissions.delete(submission);
                this.busy = false;
            }
        },
    }));
}

if (window.Livewire) installRequestHook();
else document.addEventListener('livewire:init', installRequestHook, { once: true });
if (window.Alpine) installForm();
else document.addEventListener('alpine:init', installForm, { once: true });
