<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Settings\SettingEdit;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Support\PdfContactDetails;

/*
 * The settings half of the shared "From" contact block.
 *
 * The resolver is only useful if something can write `pdf_contact_details`.
 * Before this change the only contact-details field in the UI was the
 * invoice one, which is why quote / order / delivery PDFs had no way to fill
 * the block their templates render.
 *
 * The settings blade reaches for tables the minimal TestSchema does not
 * ship, so the component is mounted through a render-stub subclass — the
 * same discipline as PdfTemplateSelectTest. mount() and save() still run for
 * real.
 */

class SettingContactDetailsEdit extends SettingEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

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
 * The fields SettingEdit::save() validates as required. Unrelated to the
 * contact block, but the save cannot run without them.
 *
 * @return array<string, string>
 */
function requiredSettings(): array
{
    return [
        'organizationName' => 'Acme Pty Ltd',
        'timezone' => 'UTC',
        'dateFormat' => 'M j, Y',
        'timeFormat' => 'H:i',
    ];
}

test('the shared contact details field round-trips through the settings form', function () {
    Livewire::test(SettingContactDetailsEdit::class)
        ->set(requiredSettings())
        ->set('pdfContactDetails', "Acme Pty Ltd\n1 Example St")
        ->call('save')
        ->assertHasNoErrors();

    app('laravel-crm.settings')->forgetCache();

    expect(app('laravel-crm.settings')->get(PdfContactDetails::SHARED_KEY))
        ->toBe("Acme Pty Ltd\n1 Example St");

    // ...and is what every doc type without an override of its own resolves.
    expect(PdfContactDetails::for('quote'))->toBe("Acme Pty Ltd\n1 Example St");
    expect(PdfContactDetails::for('order'))->toBe("Acme Pty Ltd\n1 Example St");
    expect(PdfContactDetails::for('delivery'))->toBe("Acme Pty Ltd\n1 Example St");
});

test('mount reads the persisted shared value back into the form', function () {
    app('laravel-crm.settings')->set(PdfContactDetails::SHARED_KEY, 'Persisted block');
    app('laravel-crm.settings')->forgetCache();

    Livewire::test(SettingContactDetailsEdit::class)
        ->assertSet('pdfContactDetails', 'Persisted block');
});

test('saving the shared field leaves an existing invoice override alone', function () {
    // The two fields are independent: filling the shared one must not
    // silently rewrite the From block on invoices, which is the only doc
    // type hosts already had configured.
    app('laravel-crm.settings')->set('invoice_contact_details', 'Invoice-only block');
    app('laravel-crm.settings')->forgetCache();

    Livewire::test(SettingContactDetailsEdit::class)
        ->set(requiredSettings())
        ->set('pdfContactDetails', 'Shared block')
        ->call('save')
        ->assertHasNoErrors();

    app('laravel-crm.settings')->forgetCache();

    expect(PdfContactDetails::for('invoice'))->toBe('Invoice-only block');
    expect(PdfContactDetails::for('quote'))->toBe('Shared block');
});

test('clearing the shared field stops it resolving for any doc type', function () {
    // The field is write-once if save() guards it on truthiness the way its
    // neighbouring prefix/terms fields do: clearing the textarea binds '',
    // the set() is skipped, the old row survives, and mount() restores the
    // stale value to the form. Because this one key feeds the From block on
    // four doc types at once, that would leave an admin unable to remove a
    // block printing across their documents without direct DB access.
    app('laravel-crm.settings')->set(PdfContactDetails::SHARED_KEY, 'Block to remove');
    app('laravel-crm.settings')->forgetCache();

    Livewire::test(SettingContactDetailsEdit::class)
        ->assertSet('pdfContactDetails', 'Block to remove')
        ->set(requiredSettings())
        ->set('pdfContactDetails', '')
        ->call('save')
        ->assertHasNoErrors();

    app('laravel-crm.settings')->forgetCache();

    // Persisted as an empty row rather than deleted, which the resolver's
    // filled() check reads as "not set".
    expect(app('laravel-crm.settings')->get(PdfContactDetails::SHARED_KEY))->toBe('');

    foreach (['quote', 'order', 'delivery', 'invoice'] as $docType) {
        expect(PdfContactDetails::for($docType))->toBeNull();
    }

    // ...and the form no longer resurrects it on the next visit.
    Livewire::test(SettingContactDetailsEdit::class)
        ->assertSet('pdfContactDetails', '');
});

test('an untouched shared field writes no row at all', function () {
    // The other half of the `!== null` guard: never filling the field on a
    // fresh install must not leave a null row behind for every save.
    Livewire::test(SettingContactDetailsEdit::class)
        ->set(requiredSettings())
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::where('name', PdfContactDetails::SHARED_KEY)->exists())->toBeFalse();
});
