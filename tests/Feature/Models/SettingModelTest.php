<?php

use VentureDrake\LaravelCrm\Models\Setting;

test('setting uses prefixed table', function () {
    expect((new Setting)->getTable())->toBe('crm_settings');
});

test('setting create persists a record', function () {
    Setting::create(['name' => 'tax_rate', 'value' => '10']);

    $this->assertDatabaseHas('crm_settings', ['name' => 'tax_rate', 'value' => '10']);
});
