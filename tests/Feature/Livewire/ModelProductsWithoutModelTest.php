<?php

/*
 * On a document create form (quote, order, invoice, purchase order) the
 * products component has no model yet. Its view used to call
 * class_basename($model) on that null, which logs a PHP 8.4 deprecation
 * (str_replace(): Passing null to parameter #3) on every create page and
 * becomes a TypeError in PHP 9. The component now exposes a computed
 * modelType instead.
 */

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\ModelProducts;
use VentureDrake\LaravelCrm\Models\Order;

test('it renders without a model and raises no deprecation', function () {
    $this->actingAsUser();

    // Deprecations as exceptions: the regression this guards against is a
    // warning in the log, which a plain render would silently pass.
    set_error_handler(
        fn (int $severity, string $message) => throw new ErrorException($message, 0, $severity),
        E_DEPRECATED | E_USER_DEPRECATED
    );

    try {
        $component = Livewire::test(ModelProducts::class);
    } finally {
        restore_error_handler();
    }

    $component->assertOk()
        ->assertSet('model', null);

    expect($component->instance()->modelType())->toBeNull();
});

test('the model type is the short class name of the model', function () {
    $this->actingAsUser();

    $order = Order::create(['description' => 'Order', 'currency' => 'USD']);

    $component = Livewire::test(ModelProducts::class, ['model' => $order]);

    expect($component->instance()->modelType())->toBe('Order');
});
