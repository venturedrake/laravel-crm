// Livewire 3 does not reject action promises on every request failure. Track
// requests through the public hook so Save can recover in both major versions.
const requests = new Set();
const submissions = new Set();
const clone = value => JSON.parse(JSON.stringify(value));

function installRequestHook() {
    window.Livewire.hook('request', ({ payload, succeed, fail }) => {
        const body = typeof payload === 'string' ? JSON.parse(payload) : payload;
        const components = body?.components ?? [];
        const ids = components.map(component => JSON.parse(component.snapshot).memo.id);
        // What each component's request told the server, so Save can tell
        // a value the server is still answering from one edited since.
        const updates = Object.fromEntries(components.map((component, index) => [ids[index], component.updates ?? {}]));
        let finish;
        const request = { ids, updates, done: new Promise(resolve => { finish = resolve; }) };
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

// Take what the user sees (captured) and what the server has been told
// (base). Comparing the two later tells the user's edits apart from values
// that are only stale because a server response has not arrived yet.
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
    // `canonical` is the snapshot Livewire replaces when a response arrives.
    // With the updates of the child's in-flight requests applied it is what
    // those responses will be diffed against. Without it, fall back to the
    // reactive rows: a value the user edited still differs from them on its
    // native input.
    const canonical = child.__instance?.canonical?.products;
    if (!Array.isArray(canonical)) return { rows, base: clone(child.$get('products')) };
    const data = { products: clone(canonical) };
    for (const request of requests) {
        for (const [key, value] of Object.entries(request.updates?.[child.$id] ?? {})) {
            if (key === 'products' || key.startsWith('products.')) assign(data, key, clone(value ?? null));
        }
    }
    return { rows, base: Array.isArray(data.products) ? data.products : clone(canonical) };
}

function assign(target, path, value) {
    const keys = path.split('.');
    const last = keys.pop();
    let node = target;
    for (const key of keys) {
        if (node[key] === null || typeof node[key] !== 'object') node[key] = {};
        node = node[key];
    }
    node[last] = value;
}

const blank = value => value === null || value === undefined || value === '';

// Whether two form values mean the same thing: blanks are equal, and the
// money mask's "1,200.00" equals 1200.
function same(a, b) {
    if (blank(a) || blank(b)) return blank(a) && blank(b);
    if (typeof a === 'object' || typeof b === 'object') return JSON.stringify(a) === JSON.stringify(b);
    const number = value => (typeof value === 'number' ? value : Number(String(value).replace(/,/g, '').trim()));
    const x = number(a);
    const y = number(b);
    if (!Number.isNaN(x) && !Number.isNaN(y) && String(a).trim() !== '' && String(b).trim() !== '') return x === y;
    return String(a) === String(b);
}

// Rows to prepare once pending child requests have finished. A captured
// value that differs from base is the user's edit (a variation picked, a
// quantity or price typed), so it is kept. One that equals base may only be
// stale - the server can have repriced the row since - so the latest value
// is taken. Latest decides which rows exist and in what order, so an add,
// remove or move that was in flight is respected.
function reconcile(captured, base, latest) {
    const find = (rows, row, index) => (row?.row_key !== undefined && row?.row_key !== null
        ? rows.find(candidate => candidate?.row_key === row.row_key)
        : rows[index]);
    return latest.map((row, index) => {
        const mine = find(captured, row, index);
        if (!mine) return row;
        const before = find(base, mine, index) ?? {};
        const merged = { ...row };
        for (const [field, value] of Object.entries(mine)) {
            if (!same(value, before[field])) merged[field] = value;
        }
        return merged;
    });
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
                const captured = captureRows(editor, child);
                rows = captured.rows;
                const failure = new Promise((resolve, reject) => {
                    submission = { ids: [child.$id, parent.$id], reject };
                });
                submissions.add(submission);
                const wait = action => Promise.race([action, failure]);
                // Finish an older child request before preparing the rows. Its
                // response can reprice a row - a variation picked just before
                // Save - so the captured rows are merged with what it left:
                // the user's edits win, stale values take the server's answer.
                // Waiting also protects retry values from a late child morph.
                while ([...requests].some(request => request.ids.includes(child.$id))) {
                    await wait(Promise.all([...requests].filter(request => request.ids.includes(child.$id)).map(request => request.done)));
                }
                rows = reconcile(captured.rows, captured.base, clone(child.$get('products')));
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
