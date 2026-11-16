// Livewire 3 does not reject action promises on every request failure. Track
// requests through the public hook so Save can recover in both major versions.
const requests = new Set();
const submissions = new Set();
const clone = value => JSON.parse(JSON.stringify(value));

// The product editor's own component: the one whose root holds the editor.
function isProductEditor(component) {
    const el = component?.el;
    const editor = el?.matches?.('[data-crm-product-editor]') ? el : el?.querySelector?.('[data-crm-product-editor]');
    return Boolean(editor && editor.closest('[wire\\:id]') === el);
}

// Issue #147: a response must not undo what the user typed after its request
// left. When a change replaces a whole array or object - every row changed,
// which the money mask makes usual once a form has two lines - Livewire 4
// keeps the update as a reference to the live data, not a copy. When the
// response comes, it is diffed against that update to tell what the server
// changed, and by then the update holds whatever was typed since, so the
// server's older answer reads as a change and is put over it. The server
// normalising a value ("2" comes back as 2) also reads as a change, and so
// puts a half-typed quantity back over the rest of it. Livewire 3 goes
// further: a response that changes any row replaces every row, and a
// debounced input only reaches its row once the debounce has run.
//
// The requests the editor does not send itself make this likely: Livewire
// bundles a copy of the editor into each of its parent's requests - one
// carrying a custom field with a preview event, say - and these leave at any
// moment and come back slowly. So each update is frozen as it was sent, and
// once a response has been merged, every edit its request did not carry is
// put back.

// Fields edited in the product editor by component, row and field: `value`
// is what the field must keep, `seq` tells a later edit of it apart.
const edits = new Map();
let editCount = 0;
// The editor requests in flight, by component.
const pending = new Map();

function recordEdit(event) {
    const input = event.target;
    if (!(input instanceof HTMLElement) || input.readOnly || input.type === 'hidden') return;
    const editor = input.closest('[data-crm-product-editor]');
    const binding = editor && [...input.attributes].find(attribute => attribute.name.startsWith('wire:model'));
    const match = binding?.value.match(/^products\.(\d+)\.([a-z_]+)$/);
    const root = match && editor.closest('[wire\\:id]');
    const child = root && window.Livewire.find(root.getAttribute('wire:id'));
    const rowKey = child?.$get(`products.${match[1]}.row_key`);
    if (rowKey === undefined || rowKey === null) return;
    edits.set(`${child.$id}|${rowKey}|${match[2]}`, { id: child.$id, rowKey, field: match[2], value: input.value, seq: ++editCount });
    keepTyping(input);
}

// Writing a value into the input being typed in resets its text, even an
// equal value: a number input reads "2." as 2, so the server's 2 for it puts
// the text back to "2", and the "5" typed next makes "52". While it has the
// focus, an edited input skips a write of the value it already holds. The
// money mask rewrites its input's text on every write anyway, so masked
// inputs are left to it.
function keepTyping(input) {
    const update = input._x_forceModelUpdate;
    if (typeof update !== 'function' || update.keepsTyping || [...input.attributes].some(attribute => attribute.name.startsWith('x-mask'))) return;
    const guarded = value => {
        if (document.activeElement === input && same(value, input.value)) return;
        update(value);
    };
    guarded.keepsTyping = true;
    input._x_forceModelUpdate = guarded;
}

// The row index of an edit, or -1 once its row has gone.
function rowIndex(rows, edit) {
    return Array.isArray(rows) ? rows.findIndex(row => row?.row_key === edit.rowKey) : -1;
}

// The value a request's updates give `path`, whether the update is the field
// itself or a row or array holding it.
function sentValue(updates, path) {
    for (const [key, value] of Object.entries(updates ?? {})) {
        if (key === path) return { value };
        if (!path.startsWith(`${key}.`)) continue;
        let node = value;
        for (const part of path.slice(key.length + 1).split('.')) {
            if (node === null || typeof node !== 'object') return null;
            node = node[part];
        }
        return { value: node };
    }
    return null;
}

// Freeze the updates as they are sent - the body is built from the same
// values, so what the server receives is unchanged - and note which edits
// they carry.
function sent(component, updates) {
    for (const [key, value] of Object.entries(updates ?? {})) {
        if (value !== null && typeof value === 'object') updates[key] = clone(value);
    }
    const rows = component.$wire.$get('products');
    const carried = new Map();
    for (const [key, edit] of edits) {
        const index = edit.id === component.id ? rowIndex(rows, edit) : -1;
        const value = index < 0 ? null : sentValue(updates, `products.${index}.${edit.field}`);
        if (value && same(value.value, edit.value)) carried.set(key, edit.seq);
    }
    const request = { carried };
    if (!pending.has(component.id)) pending.set(component.id, new Set());
    pending.get(component.id).add(request);
    return request;
}

// Once a request's response has been merged.
function restoreEdits(component, request) {
    const wire = component.$wire;
    const rows = wire.$get('products');
    for (const [key, edit] of edits) {
        if (edit.id !== component.id) continue;
        const index = rowIndex(rows, edit);
        if (index < 0) {
            edits.delete(key);
            continue;
        }
        const path = `products.${index}.${edit.field}`;
        const current = wire.$get(path);
        if (same(current, edit.value)) continue;
        if (request.carried.get(key) === edit.seq) {
            // The server changed the edit it was sent - held it to a
            // maximum, say. That answer is what an older response must not
            // undo.
            edit.value = clone(current ?? null);
        } else {
            // The server has not seen the edit. Put it back locally; the
            // next request sends it.
            wire.$set(path, edit.value, false);
        }
    }
}

// With nothing in flight, an edit the server already holds is done with.
function settle(component, request) {
    const requests = pending.get(component.id);
    requests?.delete(request);
    if (requests?.size) return;
    const rows = component.$wire.$get('products');
    const canonical = component.canonical?.products;
    for (const [key, edit] of edits) {
        if (edit.id !== component.id) continue;
        const index = rowIndex(rows, edit);
        const held = Array.isArray(canonical) ? canonical[index]?.[edit.field] : rows?.[index]?.[edit.field];
        if (index < 0 || same(held, edit.value)) edits.delete(key);
    }
}

function installEditGuard() {
    document.addEventListener('input', recordEdit, true);
    document.addEventListener('change', recordEdit, true);
    if (typeof window.Livewire.interceptMessage === 'function') {
        // Livewire 4: put edits back straight after the merge, before the
        // morph. onFinish runs for every message, skipped or cancelled too.
        window.Livewire.interceptMessage(({ message, onSuccess, onFinish }) => {
            if (!isProductEditor(message.component)) return;
            const request = sent(message.component, message.updates);
            onSuccess(({ onSync }) => onSync(() => restoreEdits(message.component, request)));
            onFinish(() => settle(message.component, request));
        });
        return;
    }
    // Livewire 3: the commit's updates are the ones its response is merged
    // against, and succeed runs once it has been.
    window.Livewire.hook('commit', ({ component, commit, succeed, fail }) => {
        if (!isProductEditor(component)) return;
        const request = sent(component, commit.updates);
        succeed(() => {
            restoreEdits(component, request);
            settle(component, request);
        });
        fail(() => settle(component, request));
    });
}

function installRequestHook() {
    installEditGuard();
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
