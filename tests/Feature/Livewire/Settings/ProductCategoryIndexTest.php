<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Settings\ProductCategories\ProductCategoryIndex;
use VentureDrake\LaravelCrm\Models\ProductCategory;

/*
 * #126: mount() assigned $dateFormat without the class declaring it, so every
 * load of /crm/product-categories logged a PHP 8.2+ "Creation of dynamic
 * property" deprecation. The property must be declared (and public, so the
 * value survives Livewire round-trips for headers() on re-render).
 */

beforeEach(function () {
    $this->actingAsUser(['crm_access' => 1]);
});

test('index declares dateFormat and raises no dynamic property deprecation', function () {
    ProductCategory::create(['name' => 'Hardware']);

    $deprecations = [];
    set_error_handler(function (int $level, string $message) use (&$deprecations) {
        $deprecations[] = $message;

        return true;
    }, E_DEPRECATED);

    try {
        $component = Livewire::test(ProductCategoryIndex::class)
            ->assertOk()
            ->assertSee('Hardware')
            ->set('sortBy', ['column' => 'name', 'direction' => 'asc'])
            ->assertOk()
            ->assertSee('Hardware');
    } finally {
        restore_error_handler();
    }

    expect(array_filter($deprecations, fn (string $message) => str_contains($message, 'dateFormat')))->toBeEmpty();
    expect($component->get('dateFormat'))
        ->toBe(app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format')));
});
