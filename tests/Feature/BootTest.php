<?php

test('application boots', function () {
    expect(true)->toBeTrue();
    expect(app('laravel-crm'))->not->toBeNull();
});
