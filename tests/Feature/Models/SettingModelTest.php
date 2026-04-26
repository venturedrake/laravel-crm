<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Tests\TestCase;

class SettingModelTest extends TestCase
{
    public function test_setting_uses_prefixed_table(): void
    {
        $this->assertSame('crm_settings', (new Setting)->getTable());
    }

    public function test_setting_create_persists_a_record(): void
    {
        Setting::create([
            'name' => 'tax_rate',
            'value' => '10',
        ]);

        $this->assertDatabaseHas('crm_settings', [
            'name' => 'tax_rate',
            'value' => '10',
        ]);
    }
}
