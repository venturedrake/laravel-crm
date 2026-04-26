<?php

namespace VentureDrake\LaravelCrm\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrm\Tests\TestCase;

class SettingServiceTest extends TestCase
{
    private function service(): SettingService
    {
        return app('laravel-crm.settings');
    }

    public function test_set_creates_a_new_setting(): void
    {
        $setting = $this->service()->set('lead_prefix', 'L', 'Lead Prefix');

        $this->assertInstanceOf(Setting::class, $setting);
        $this->assertDatabaseHas('crm_settings', [
            'name' => 'lead_prefix',
            'value' => 'L',
            'label' => 'Lead Prefix',
        ]);
    }

    public function test_set_updates_an_existing_setting(): void
    {
        $this->service()->set('currency', 'USD');
        $this->service()->set('currency', 'AUD');

        $this->assertSame(1, Setting::where('name', 'currency')->count());
        $this->assertSame('AUD', Setting::where('name', 'currency')->first()->value);
    }

    public function test_get_returns_default_when_setting_missing(): void
    {
        $this->assertSame('fallback', $this->service()->get('does_not_exist', 'fallback'));
        $this->assertNull($this->service()->get('does_not_exist'));
    }

    public function test_all_returns_settings_keyed_by_name(): void
    {
        $this->service()->set('a', '1');
        $this->service()->set('b', '2');

        // forget cache from set being called before all()
        $this->service()->forgetCache();

        $all = $this->service()->all();

        $this->assertSame('1', $all['a']);
        $this->assertSame('2', $all['b']);
    }

    public function test_all_is_cached(): void
    {
        $this->service()->set('cached', 'first');
        $this->service()->forgetCache();
        $this->assertSame('first', $this->service()->get('cached'));

        // Bypass the service to mutate underlying row directly
        Setting::where('name', 'cached')->update(['value' => 'second']);

        // Cached value still returned
        $this->assertSame('first', $this->service()->get('cached'));

        $this->service()->forgetCache();
        $this->assertSame('second', $this->service()->get('cached'));
    }

    public function test_first_returns_underlying_model(): void
    {
        $this->service()->set('lookup', 'value');

        $found = $this->service()->first('lookup');

        $this->assertInstanceOf(Setting::class, $found);
        $this->assertSame('value', $found->value);
    }

    public function test_forget_cache_removes_cached_entry(): void
    {
        $this->service()->set('x', 'y');
        $this->service()->all();

        $this->assertTrue(Cache::has('app.crm-settings'));

        $this->service()->forgetCache();

        $this->assertFalse(Cache::has('app.crm-settings'));
    }
}
