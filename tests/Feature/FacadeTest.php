<?php

use VentureDrake\LaravelCrm\LaravelCrm;

test('facade resolves underlying class', function () {
    expect(\LaravelCrm::getFacadeRoot())->toBeInstanceOf(LaravelCrm::class);
});

test('facade methods exist', function () {
    $crm = app('laravel-crm');

    expect(method_exists($crm, 'searchLeads'))->toBeTrue();
    expect(method_exists($crm, 'getLeads'))->toBeTrue();
    expect(method_exists($crm, 'getLead'))->toBeTrue();
});
