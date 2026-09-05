<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Settings\SettingEdit;
use VentureDrake\LaravelCrm\Models\Setting;

/*
 * The General settings page is split across tabs (General, Record IDs,
 * Documents, Quotes, Invoices, Purchase orders) rather than one flat column.
 */

beforeEach(function () {
    $this->actingAsUser(['crm_access' => 1]);
    Gate::before(fn () => true);

    Setting::query()->delete();

    // mount() hangs the organisation's phone / email / address forms off the
    // `team` Setting row and dereferences it without a null guard, so the
    // component cannot boot without one. Every install has it — laravelcrm:install
    // creates it.
    Setting::create(['name' => 'team', 'value' => 'Acme Pty Ltd']);

    app('laravel-crm.settings')->forgetCache();
});

/**
 * The fields SettingEdit::save() validates as required — all of them on the
 * General tab. Unrelated to tabs, but no save runs without them.
 *
 * @return array<string, string>
 */
function requiredTabSettings(): array
{
    return [
        'organizationName' => 'Acme Pty Ltd',
        'timezone' => 'UTC',
        'dateFormat' => 'M j, Y',
        'timeFormat' => 'H:i',
    ];
}

test('mount defaults the tab to general', function () {
    Livewire::test(SettingEdit::class)
        ->assertSet('tab', 'general');
});

test('a tab can be deep-linked from the query string', function () {
    Livewire::withQueryParams(['tab' => 'invoices'])
        ->test(SettingEdit::class)
        ->assertSet('tab', 'invoices');

    Livewire::withQueryParams(['tab' => 'purchase-orders'])
        ->test(SettingEdit::class)
        ->assertSet('tab', 'purchase-orders');
});

test('an empty tab query param falls back to general', function () {
    // `?tab=` arrives as '' rather than absent. Left alone it checks no radio,
    // and .tab-content is display:none until one is — a tab strip over a void.
    Livewire::withQueryParams(['tab' => ''])
        ->test(SettingEdit::class)
        ->assertSet('tab', 'general');
});

test('an unrecognised tab query param falls back to general', function () {
    Livewire::withQueryParams(['tab' => 'bogus'])
        ->test(SettingEdit::class)
        ->assertSet('tab', 'general');
});

test('a real tab whose module is disabled falls back to general', function () {
    config()->set('laravel-crm.modules', ['leads']);

    Livewire::withQueryParams(['tab' => 'invoices'])
        ->test(SettingEdit::class)
        ->assertSet('tab', 'general');
});

test('setting the tab property to an unknown value normalises it', function () {
    // Guards $wire.set('tab', ...) from the console the way mount() guards the
    // query string.
    Livewire::test(SettingEdit::class)
        ->set('tab', 'invoices')
        ->assertSet('tab', 'invoices')
        ->set('tab', 'bogus')
        ->assertSet('tab', 'general');
});

test('a validation failure jumps to the tab holding the offending field', function () {
    // Every field submits on every save regardless of the visible tab, so a
    // failure can land on a panel the admin cannot see. Without the jump, Save
    // looks inert.
    Livewire::withQueryParams(['tab' => 'invoices'])
        ->test(SettingEdit::class)
        ->set(requiredTabSettings())
        ->set('organizationName', '')
        ->call('save')
        ->assertHasErrors(['organizationName'])
        ->assertSet('tab', 'general');
});

test('fields on hidden tabs still save from whichever tab is showing', function () {
    // The whole point of one page with tabs rather than several pages: one
    // atomic save covers every panel. Also locks out anyone later branching
    // save() on $tab.
    Livewire::test(SettingEdit::class)
        ->assertSet('tab', 'general')
        ->set(requiredTabSettings())
        ->set('quoteTerms', 'Quote terms text')
        ->set('invoiceTerms', 'Invoice terms text')
        ->set('purchaseOrderTerms', 'Purchase order terms text')
        ->set('leadPrefix', 'LD')
        ->set('pdfContactDetails', "Acme Pty Ltd\n1 Example St")
        ->call('save')
        ->assertHasNoErrors();

    app('laravel-crm.settings')->forgetCache();

    expect(app('laravel-crm.settings')->get('quote_terms'))->toBe('Quote terms text');
    expect(app('laravel-crm.settings')->get('invoice_terms'))->toBe('Invoice terms text');
    expect(app('laravel-crm.settings')->get('purchase_order_terms'))->toBe('Purchase order terms text');
    expect(app('laravel-crm.settings')->get('lead_prefix'))->toBe('LD');
    expect(app('laravel-crm.settings')->get('pdf_contact_details'))->toBe("Acme Pty Ltd\n1 Example St");
});

test('render exposes the full tab set, and module gating removes whole tabs', function () {
    Livewire::test(SettingEdit::class)
        ->assertViewIs('laravel-crm::livewire.settings.setting-edit')
        ->assertViewHas('tabs', [
            'general', 'record-ids', 'documents', 'quotes', 'invoices', 'purchase-orders',
        ]);

    // An empty array is "every module on", not "every module off" — the trap
    // Modules::enabled() exists to keep out of the tab set.
    config()->set('laravel-crm.modules', []);

    Livewire::test(SettingEdit::class)
        ->assertViewHas('tabs', [
            'general', 'record-ids', 'documents', 'quotes', 'invoices', 'purchase-orders',
        ]);

    // Record IDs covers four entities, so it survives while any one is on...
    config()->set('laravel-crm.modules', ['leads']);

    Livewire::test(SettingEdit::class)
        ->assertViewHas('tabs', ['general', 'record-ids', 'documents']);

    // ...and disappears when none of them is, while Documents never gates.
    config()->set('laravel-crm.modules', ['quotes']);

    Livewire::test(SettingEdit::class)
        ->assertViewHas('tabs', ['general', 'documents', 'quotes']);
});

test('each tab radio is immediately followed by its own panel', function () {
    // DaisyUI reveals a panel with `:checked + .tab-content`, so a panel that
    // is not its radio's next element sibling ends up controlled by the
    // *previous* tab — two panels open at once. Any module conditional has to
    // wrap the input and the panel together for this to hold.
    config()->set('laravel-crm.modules', ['leads', 'purchase-orders']);

    $html = Livewire::test(SettingEdit::class)->html();

    preg_match_all(
        '/<input[^>]*name="setting-tabs"[^>]*value="([^"]*)"[^>]*\/>\s*<div role="tabpanel"[^>]*wire:key="setting-tab-panel-([^"]*)"/s',
        $html,
        $pairs
    );

    expect($pairs[1])->toBe(['general', 'record-ids', 'documents', 'purchase-orders']);
    expect($pairs[2])->toBe(['general', 'record-ids', 'documents', 'purchase-orders']);

    // No stray panels beyond the paired ones.
    expect(substr_count($html, 'role="tabpanel"'))->toBe(4);
});

test('module-gated fields stay gated inside their tabs', function () {
    config()->set('laravel-crm.modules', ['leads']);

    $html = Livewire::test(SettingEdit::class)->html();

    expect($html)->toContain('wire:model="leadPrefix"');

    foreach (['dealPrefix', 'orderPrefix', 'deliveryPrefix'] as $field) {
        expect($html)->not->toContain('wire:model="'.$field.'"');
    }

    // The shared block and the cross-document toggle are never gated.
    expect($html)->toContain('wire:model="pdfContactDetails"');
    expect($html)->toContain('wire:model="dynamicProducts"');
});

test('SettingEdit blade view uses the shared tab markup', function () {
    $bladePath = __DIR__.'/../../../../resources/views/livewire/settings/setting-edit.blade.php';

    expect(file_exists($bladePath))->toBeTrue();

    $blade = file_get_contents($bladePath);

    expect($blade)->toContain('class="tabs tabs-lift"');
    expect($blade)->toContain('role="tab"');
    expect($blade)->toContain('role="tabpanel"');
    expect($blade)->toContain('wire:model.live="tab"');
    expect($blade)->toContain('wire:submit="save"');

    // Must not collide with the `template-tabs` radio group on Settings →
    // Templates; two groups sharing a name would fight over which is checked.
    expect($blade)->toContain('name="setting-tabs"');

    // MaryUI spreads `required` onto the native control, and a browser refuses
    // to submit a form holding an invalid control it cannot focus — which every
    // required field on a display:none panel is. Without novalidate, Save is a
    // dead button with no error and no request. rules() still enforces.
    expect($blade)->toContain('novalidate');
});

test('the shared contact details field is not gated behind a module directive', function () {
    // It prints on quote, order, delivery and invoice PDFs, so gating it on any
    // one module would leave the other installs unable to fill it.
    $blade = file_get_contents(__DIR__.'/../../../../resources/views/livewire/settings/setting-edit.blade.php');

    $position = strpos($blade, 'wire:model="pdfContactDetails"');

    expect($position)->not->toBeFalse();

    // Walk back to the nearest module directive and confirm it was closed
    // before this field, i.e. the field sits outside every @has*enabled block.
    preg_match_all('/@(end)?has\w+enabled/', substr($blade, 0, $position), $directives);

    $open = 0;

    foreach ($directives[0] as $directive) {
        $open += str_starts_with($directive, '@end') ? -1 : 1;
    }

    expect($open)->toBe(0);
});
