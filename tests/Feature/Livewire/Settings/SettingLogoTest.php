<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Settings\SettingEdit;
use VentureDrake\LaravelCrm\Models\Setting;

/*
 * Removing the organisation logo from Settings → General.
 *
 * The delete is immediate rather than deferred to save(), because the preview
 * it clears is the only feedback the logo is gone — staging it until Save would
 * show a logo-less field while every PDF still printed the old artwork.
 */

beforeEach(function () {
    $this->actingAsUser(['crm_access' => 1]);
    Gate::before(fn () => true);

    Setting::query()->delete();

    // mount() dereferences the `team` Setting row without a null guard.
    Setting::create(['name' => 'team', 'value' => 'Acme Pty Ltd']);

    Storage::fake('public');

    app('laravel-crm.settings')->forgetCache();
});

/**
 * Put a saved logo on the install, both the file and the two settings rows the
 * upload branch of save() writes.
 */
function storeSavedLogo(string $path = 'laravel-crm/acme.png'): void
{
    Storage::disk('public')->put($path, 'PNGDATA');

    app('laravel-crm.settings')->set('logo_file', $path);
    app('laravel-crm.settings')->set('logo_file_name', basename($path));
    app('laravel-crm.settings')->forgetCache();
}

test('deleting a saved logo clears both settings rows and the file', function () {
    storeSavedLogo();

    Livewire::test(SettingEdit::class)
        ->assertSet('logo', 'laravel-crm/acme.png')
        ->call('deleteLogo')
        ->assertHasNoErrors()
        ->assertSet('logo', null);

    app('laravel-crm.settings')->forgetCache();

    expect(app('laravel-crm.settings')->get('logo_file'))->toBeNull();
    expect(app('laravel-crm.settings')->get('logo_file_name'))->toBeNull();
    expect(Storage::disk('public')->exists('laravel-crm/acme.png'))->toBeFalse();
});

test('a deleted logo stays gone on the next visit', function () {
    storeSavedLogo();

    Livewire::test(SettingEdit::class)->call('deleteLogo');

    app('laravel-crm.settings')->forgetCache();

    Livewire::test(SettingEdit::class)->assertSet('logo', null);
});

test('deleting a pending upload reverts to the saved logo rather than destroying it', function () {
    // Pressing remove after picking the wrong file must not also destroy the
    // logo that is still printing on every PDF.
    storeSavedLogo();

    Livewire::test(SettingEdit::class)
        ->set('logoFile', UploadedFile::fake()->image('replacement.png'))
        ->call('deleteLogo')
        ->assertHasNoErrors()
        ->assertSet('logoFile', null)
        ->assertSet('logo', 'laravel-crm/acme.png');

    app('laravel-crm.settings')->forgetCache();

    expect(app('laravel-crm.settings')->get('logo_file'))->toBe('laravel-crm/acme.png');
    expect(Storage::disk('public')->exists('laravel-crm/acme.png'))->toBeTrue();
});

test('deleting with no logo at all is a no-op', function () {
    Livewire::test(SettingEdit::class)
        ->call('deleteLogo')
        ->assertHasNoErrors();

    // Not even an empty row — an install that never had a logo gains nothing.
    expect(Setting::where('name', 'logo_file')->exists())->toBeFalse();
});

test('a logo whose file has already vanished still clears the settings rows', function () {
    // The settings rows are the source of truth for whether a logo exists, so a
    // missing file (or an unreadable disk) must not strand the CRM showing one
    // the admin has deleted.
    app('laravel-crm.settings')->set('logo_file', 'laravel-crm/missing.png');
    app('laravel-crm.settings')->set('logo_file_name', 'missing.png');
    app('laravel-crm.settings')->forgetCache();

    Livewire::test(SettingEdit::class)
        ->call('deleteLogo')
        ->assertHasNoErrors()
        ->assertSet('logo', null);

    app('laravel-crm.settings')->forgetCache();

    expect(app('laravel-crm.settings')->get('logo_file'))->toBeNull();
});

test('deleting the logo leaves the rest of the settings form alone', function () {
    // The delete writes straight through rather than going via save(), so it
    // must not disturb the unsaved state of the form around it.
    storeSavedLogo();
    app('laravel-crm.settings')->set('organization_name', 'Acme Pty Ltd');
    app('laravel-crm.settings')->forgetCache();

    Livewire::test(SettingEdit::class)
        ->set('quoteTerms', 'Unsaved terms')
        ->set('tab', 'quotes')
        ->call('deleteLogo')
        ->assertSet('logo', null)
        ->assertSet('quoteTerms', 'Unsaved terms')
        ->assertSet('tab', 'quotes');

    app('laravel-crm.settings')->forgetCache();

    // ...and did not quietly persist the rest of the form on its way past.
    expect(app('laravel-crm.settings')->get('quote_terms'))->toBeNull();
});

test('the logo preview, remove button and file input are one field under one label', function () {
    storeSavedLogo();

    $html = Livewire::test(SettingEdit::class)->html();

    // One legend, not two — passing a label to x-mary-file as well would print
    // a second one mid-field.
    expect(substr_count($html, '>Logo</legend>'))->toBe(1);

    $legend = strpos($html, '>Logo</legend>');
    $preview = strpos($html, 'logo-preview');
    $remove = strpos($html, 'deleteLogo');
    $fileInput = strpos($html, 'type="file"');

    expect($legend)->toBeLessThan($preview);
    expect($preview)->toBeLessThan($remove);
    expect($remove)->toBeLessThan($fileInput);
});

test('no preview frame renders when there is no logo', function () {
    $html = Livewire::test(SettingEdit::class)->html();

    expect($html)->not->toContain('deleteLogo');
    expect($html)->not->toContain('border-radius: var(--radius-field)');

    // The field itself is still there to upload into.
    expect(substr_count($html, '>Logo</legend>'))->toBe(1);
});

test('the preview frame draws an input-style border and corners the remove button', function () {
    storeSavedLogo();

    $html = Livewire::test(SettingEdit::class)->html();

    // `--input-color` is declared by DaisyUI only inside `.input` / `.select` /
    // `.textarea`, never at the theme root. Reading it from a plain div
    // resolves to nothing and invalidates the whole declaration, leaving the
    // frame with no border at all — which is exactly what this markup did
    // before. The border must therefore be built from root-scoped tokens.
    expect($html)->not->toContain('var(--input-color)');
    expect($html)->toContain('border-width: var(--border)');
    expect($html)->toContain('border-radius: var(--radius-field)');
    expect($html)->toContain('var(--color-base-content)');

    // Width, style and colour stay separate declarations, so a browser without
    // color-mix loses the tint rather than the whole border.
    expect($html)->toContain('border-style: solid');

    // The remove button is a flex sibling of the image, not an overlay on it:
    // absolutely positioning it over the top-right corner hid the end of any
    // logo wider than it is tall. Nothing in the frame may be `absolute`.
    expect($html)->toMatch('/class="w-fit[^"]*flex items-start[^"]*"/');
    expect($html)->not->toMatch('/class="[^"]*absolute[^"]*"[^>]*wire:click="deleteLogo"/');

    // The image is bounded rather than fixed, so neither a very wide nor a very
    // tall logo escapes the frame — and object-contain keeps it undistorted
    // when a limit bites.
    expect($html)->toContain('max-h-24');
    expect($html)->toContain('object-contain');
});
