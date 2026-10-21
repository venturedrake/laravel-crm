<?php

use Illuminate\Support\Facades\Gate;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Quote;

test('manageProducts is granted by the entity edit permission, not the product permission', function () {
    $this->actingAsUserWithPermissions([
        'edit crm deals', 'edit crm quotes', 'edit crm orders',
    ]);

    expect(Gate::allows('manageProducts', Deal::class))->toBeTrue()
        ->and(Gate::allows('manageProducts', Quote::class))->toBeTrue()
        ->and(Gate::allows('manageProducts', Order::class))->toBeTrue();
});

test('manageProducts is denied without the matching edit permission', function () {
    $this->actingAsUserWithPermissions(['view crm deals', 'edit crm products']);

    expect(Gate::allows('manageProducts', Deal::class))->toBeFalse()
        ->and(Gate::allows('manageProducts', Quote::class))->toBeFalse()
        ->and(Gate::allows('manageProducts', Order::class))->toBeFalse();
});

test('the activity policy resolves through the gate', function () {
    $this->actingAsUserWithPermissions(['view crm activities']);

    expect(Gate::allows('viewAny', Activity::class))->toBeTrue();
});
