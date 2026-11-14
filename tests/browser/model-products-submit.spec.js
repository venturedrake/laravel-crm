import { test, expect } from '@playwright/test';

for (const timing of ['debounce', 'in-flight']) {
    test(`fractional delivery saves the visible quantity with ${timing} pending`, async ({ page }, testInfo) => {
        const requests = [];
        page.on('request', request => {
            if (request.method() === 'POST' && request.url().includes('/livewire')) requests.push(request.postDataJSON());
        });
        const fixture = await (await page.request.get('/fixture/start')).json();
        await page.goto(`/fixture/delivery/${fixture.order}`);
        const quantity = page.locator('input[wire\\:model\\.live\\.debounce\\.500ms="products.0.quantity"]');
        await expect(quantity).toHaveValue('1.25');
        // Wait for initial child-to-parent preview before injecting the race.
        await page.waitForTimeout(800);
        let release;
        const held = new Promise(resolve => { release = resolve; });
        let started;
        const sent = new Promise(resolve => { started = resolve; });
        await page.route('**/livewire*/**', async route => {
            if (route.request().method() !== 'POST') return route.continue();
            const body = route.request().postDataJSON();
            if (body?.components?.some(c => Object.keys(c.updates).some(key => key.startsWith('products')))) {
                started();
                await held;
            }
            await route.continue();
        });
        if (timing === 'in-flight') {
            await quantity.fill('1');
            await sent;
        }
        // Set and submit in one browser task, well inside the 500ms debounce.
        await quantity.evaluate(input => {
            input.value = '0.5';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.closest('form').requestSubmit();
        });
        await page.waitForTimeout(150);
        release();
        await expect.poll(async () => (await (await page.request.get(`/fixture/result/${fixture.order}`)).json()).quantities).toEqual([1.25, 0.5]);
        expect((await (await page.request.get(`/fixture/result/${fixture.order}`)).json()).remainder).toBe(0.75);
        const calls = requests.flatMap(request => request.components ?? []).flatMap(component => component.calls);
        const preparation = calls.findIndex(call => call.method === 'prepareForSave');
        const persistence = calls.findIndex(call => call.method === 'saveWithProducts');
        expect(preparation).toBeGreaterThanOrEqual(0);
        expect(persistence).toBeGreaterThan(preparation);
        expect(calls[persistence].params[0].products[0].quantity).toBe('0.5');
        await testInfo.attach('livewire-requests', { body: JSON.stringify(requests, null, 2), contentType: 'application/json' });
    });
}

const quantityInput = 'input[wire\\:model\\.live\\.debounce\\.500ms="products.0.quantity"]';
const field = name => `input[wire\\:model\\.live\\.debounce\\.500ms="products.0.${name}"]`;

async function start(page) {
    return (await page.request.get('/fixture/start')).json();
}

async function readyForm(page, family, fixture, edit) {
    await page.goto(`/fixture/form/${family}${edit ? '/'+fixture.documents[family] : ''}`);
    await expect(page.locator('[data-crm-product-editor]')).toBeVisible();
    await page.evaluate(async ({ family, product, edit }) => {
        const form = document.querySelector('form[x-data="crmProductForm"]');
        const parent = Livewire.find(form.closest('[wire\\:id]').getAttribute('wire:id'));
        await parent.$set('organization_name', 'Browser customer', false);
        if (family === 'purchase-order') await parent.$set('delivery_type', 'pickup', false);
        if (family === 'quote') await parent.$set('title', 'Browser saved document', false);
        if (!edit) {
            const editor = document.querySelector('[data-crm-product-editor]');
            await Livewire.find(editor.closest('[wire\\:id]').getAttribute('wire:id')).$set('products.0.id', product);
        }
    }, { family, product: fixture.product, edit });
    await page.waitForTimeout(600);
}

for (const family of ['quote', 'order', 'invoice', 'purchase-order']) {
    for (const edit of [false, true]) {
        test(`${family} ${edit ? 'edit' : 'create'} captures immediate quantity, price and comment`, async ({ page }) => {
            const fixture = await start(page);
            await readyForm(page, family, fixture, edit);
            await page.locator(quantityInput).evaluate((input) => {
                const form = input.closest('form');
                const set = (name, value) => {
                    const control = [...form.querySelectorAll('input')].find(el => [...el.attributes].some(a => a.name.startsWith('wire:model') && a.value === `products.0.${name}`));
                    control.value = value;
                    control.dispatchEvent(new Event('input', { bubbles: true }));
                };
                set('quantity', '0.5');
                set('unit_price', '12.00');
                set('comments', 'Captured before debounce');
                form.requestSubmit();
            });
            const result = async () => (await page.request.get(`/fixture/document/${family}/${fixture.product}`)).json();
            await expect.poll(async () => (await result()).lines[0].quantity).toBe(0.5);
            const saved = await result();
            expect(saved.lines[0].price).toBe(1200);
            expect(saved.lines[0].amount).toBe(600);
            expect(saved.lines[0].comments).toBe('Captured before debounce');
            expect(saved.document.subtotal).toBe(600);
            await page.goto(`/fixture/form/${family}/${saved.document.id}`);
            await expect(page.locator(quantityInput)).toHaveValue('0.5');
            await expect(page.locator(field('comments'))).toHaveValue('Captured before debounce');
        });
    }
}

test('delivery editing captures the value on a Save button click', async ({ page }) => {
    const fixture = await start(page);
    await page.goto(`/fixture/delivery-edit/${fixture.delivery}`);
    await expect(page.locator(quantityInput)).toHaveValue('1.25');
    await page.waitForTimeout(600);
    await page.locator(quantityInput).fill('0.5');
    await page.getByRole('button', { name: 'Save changes', exact: true }).click();
    await expect.poll(async () => (await (await page.request.get(`/fixture/result/${fixture.order}`)).json()).quantities).toEqual([0.5]);
    await page.goto(`/fixture/delivery-edit/${fixture.delivery}`);
    await expect(page.locator(quantityInput)).toHaveValue('0.5');
});

test('double submit persists once while preparation is delayed', async ({ page }) => {
    const fixture = await start(page);
    await page.goto(`/fixture/delivery/${fixture.order}`);
    await page.waitForTimeout(600);
    let preparations = 0;
    await page.route('**/livewire*/**', async route => {
        const body = route.request().method() === 'POST' ? route.request().postDataJSON() : null;
        if (body?.components?.some(c => c.calls.some(call => call.method === 'prepareForSave'))) {
            preparations++;
            await new Promise(resolve => setTimeout(resolve, 800));
        }
        await route.continue();
    });
    await page.locator(quantityInput).evaluate(input => {
        input.value = '0.5';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.closest('form').requestSubmit();
        input.closest('form').requestSubmit();
    });
    await expect(page.locator(quantityInput)).toBeDisabled();
    await expect.poll(async () => (await (await page.request.get(`/fixture/result/${fixture.order}`)).json()).quantities).toEqual([1.25, 0.5]);
    expect(preparations).toBe(1);
});

test('validation failure preserves the input and permits a keyboard retry', async ({ page }) => {
    const fixture = await start(page);
    await readyForm(page, 'quote', fixture, false);
    await page.evaluate(() => {
        const form = document.querySelector('form[x-data="crmProductForm"]');
        Livewire.find(form.closest('[wire\\:id]').getAttribute('wire:id')).$set('title', '', false);
    });
    await page.locator(quantityInput).fill('0.5');
    await page.getByRole('button', { name: 'Save', exact: true }).click();
    await expect(page.getByText('The title field is required.')).toBeVisible();
    await expect(page.locator(quantityInput)).toBeEnabled();
    await expect(page.locator(quantityInput)).toHaveValue('0.5');
    await page.locator('input[wire\\:model="title"]').fill('Retry quote');
    await page.locator(quantityInput).press('Enter');
    await expect.poll(async () => (await (await page.request.get(`/fixture/document/quote/${fixture.product}`)).json()).lines[0].quantity).toBe(0.5);
});

for (const action of ['prepareForSave', 'saveWithProducts']) {
    test(`network failure during ${action} restores editing and allows retry`, async ({ page }) => {
        const fixture = await start(page);
        await page.goto(`/fixture/delivery/${fixture.order}`);
        await page.waitForTimeout(600);
        let failed = false;
        await page.route('**/livewire*/**', async route => {
            const body = route.request().method() === 'POST' ? route.request().postDataJSON() : null;
            if (!failed && body?.components?.some(c => c.calls.some(call => call.method === action))) {
                failed = true;
                return route.abort();
            }
            await route.continue();
        });
        await page.locator(quantityInput).fill('0.5');
        await page.getByRole('button', { name: 'Save', exact: true }).click();
        await expect(page.getByRole('alert')).toContainText('Unable to save');
        await expect(page.locator(quantityInput)).toBeEnabled();
        await expect(page.locator(quantityInput)).toHaveValue('0.5');
        await page.getByRole('button', { name: 'Save', exact: true }).click();
        await expect.poll(async () => (await (await page.request.get(`/fixture/result/${fixture.order}`)).json()).quantities).toEqual([1.25, 0.5]);
    });
}

for (const family of ['quote', 'order']) {
    test(`Enter in the create-product drawer on a ${family} creates the product, not the ${family}`, async ({ page }) => {
        const fixture = await start(page);
        await readyForm(page, family, fixture, false);
        const quotesBefore = (await (await page.request.get('/fixture/product-created/none')).json()).quotes;
        await page.evaluate(async () => {
            const editor = document.querySelector('[data-crm-product-editor]');
            await Livewire.find(editor.closest('[wire\\:id]').getAttribute('wire:id')).$set('showCreateProduct', true);
        });
        const name = page.locator('[data-crm-product-editor] input[wire\\:model="name"]');
        await expect(name).toBeVisible();
        await name.fill('Drawer product');
        await name.press('Enter');
        const created = async () => (await page.request.get('/fixture/product-created/Drawer%20product')).json();
        await expect.poll(async () => (await created()).product).toBe(true);
        await page.waitForTimeout(600);
        expect((await created()).quotes).toBe(quotesBefore);
        await expect(page.locator('form[x-data="crmProductForm"]')).toBeVisible();
        await expect(page.getByRole('alert')).toHaveCount(0);
    });
}

test('deal rows still submit directly from their parent', async ({ page }) => {
    const fixture = await start(page);
    await page.goto(`/fixture/form/deal/${fixture.documents.deal}`);
    await expect(page.locator('[data-crm-product-editor]')).toHaveCount(0);
    const quantity = page.locator('input[wire\\:model\\.live="products.0.quantity"]');
    await expect(quantity).toHaveValue('1.25');
    await page.evaluate(() => {
        const form = document.querySelector('form');
        const parent = Livewire.find(form.closest('[wire\\:id]').getAttribute('wire:id'));
        parent.$set('organization_name', 'Browser customer', false);
    });
    await quantity.fill('0.5');
    await page.getByRole('button', { name: 'Save changes', exact: true }).click();
    await expect.poll(async () => (await (await page.request.get(`/fixture/document/deal/${fixture.product}`)).json()).lines[0].quantity).toBe(0.5);
});
