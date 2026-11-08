<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Settings\SettingEdit;
use VentureDrake\LaravelCrm\Livewire\Settings\TaxRates\TaxRateCreate;
use VentureDrake\LaravelCrm\Livewire\Settings\TaxRates\TaxRateEdit;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\TaxRate;

/*
 * #104: a tax rate must be a number from 0 to 100.
 *
 * `rate` used to be only `required`, so `abc` reached the decimal column and
 * the page returned a 500, while `-5` and `250` were saved. The default tax
 * rate on the settings page had no validation at all, and a text value there
 * later breaks `number_format()` on the product form.
 */

beforeEach(function () {
    $this->actingAsUser(['crm_access' => 1]);
});

test('create rejects a rate that is not a number from 0 to 100', function (string $rate) {
    Livewire::test(TaxRateCreate::class)
        ->set('name', 'Bad')
        ->set('rate', $rate)
        ->call('save')
        ->assertHasErrors(['rate']);

    expect(TaxRate::count())->toBe(0);
})->with(['abc', '-5', '100.01']);

test('create accepts a rate from 0 to 100', function (string $rate) {
    Livewire::test(TaxRateCreate::class)
        ->set('name', 'Good')
        ->set('rate', $rate)
        ->call('save')
        ->assertHasNoErrors();

    expect((float) TaxRate::first()->rate)->toBe((float) $rate);
})->with(['0', '12.5', '100']);

test('edit rejects an invalid rate and keeps the stored one', function (string $rate) {
    // fresh(): load it as route binding would, with the column default for `default`.
    $taxRate = TaxRate::create(['name' => 'GST', 'rate' => 10])->fresh();

    Livewire::test(TaxRateEdit::class, ['taxRate' => $taxRate])
        ->set('rate', $rate)
        ->call('save')
        ->assertHasErrors(['rate']);

    expect((float) $taxRate->fresh()->rate)->toBe(10.0);
})->with(['abc', '-5']);

test('legacy store route returns a field error for a text rate', function () {
    $this->from(route('laravel-crm.tax-rates.create'))
        ->post(route('laravel-crm.tax-rates.store'), ['name' => 'Bad', 'rate' => 'abc'])
        ->assertRedirect(route('laravel-crm.tax-rates.create'))
        ->assertSessionHasErrors('rate');

    expect(TaxRate::count())->toBe(0);
});

describe('settings default tax rate', function () {
    beforeEach(function () {
        Gate::before(fn () => true);

        Setting::query()->delete();

        // SettingEdit::mount() needs the `team` Setting row (see SettingEditTabsTest).
        Setting::create(['name' => 'team', 'value' => 'Acme Pty Ltd']);

        app('laravel-crm.settings')->forgetCache();
    });

    $required = [
        'organizationName' => 'Acme Pty Ltd',
        'timezone' => 'UTC',
        'dateFormat' => 'M j, Y',
        'timeFormat' => 'H:i',
    ];

    test('a text default tax rate is a field error on the general tab', function () use ($required) {
        Livewire::withQueryParams(['tab' => 'invoices'])
            ->test(SettingEdit::class)
            ->set($required)
            ->set('taxRate', 'abc')
            ->call('save')
            ->assertHasErrors(['taxRate'])
            ->assertSet('tab', 'general');

        app('laravel-crm.settings')->forgetCache();

        expect(app('laravel-crm.settings')->get('tax_rate'))->toBeNull();
    });

    test('a 0% default tax rate is saved', function () use ($required) {
        Livewire::test(SettingEdit::class)
            ->set($required)
            ->set('taxRate', '0')
            ->call('save')
            ->assertHasNoErrors();

        app('laravel-crm.settings')->forgetCache();

        expect((string) app('laravel-crm.settings')->get('tax_rate'))->toBe('0');
    });
});
