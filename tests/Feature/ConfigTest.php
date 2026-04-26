<?php

namespace VentureDrake\LaravelCrm\Tests\Feature;

use VentureDrake\LaravelCrm\Tests\TestCase;

class ConfigTest extends TestCase
{
    public function test_default_db_table_prefix_is_crm(): void
    {
        $this->assertSame('crm_', config('laravel-crm.db_table_prefix'));
    }

    public function test_default_route_prefix_is_crm(): void
    {
        $this->assertSame('crm', config('laravel-crm.route_prefix'));
    }

    public function test_default_modules_array_includes_all_features(): void
    {
        $modules = config('laravel-crm.modules');

        $this->assertIsArray($modules);
        $this->assertContains('leads', $modules);
        $this->assertContains('deals', $modules);
        $this->assertContains('quotes', $modules);
        $this->assertContains('orders', $modules);
        $this->assertContains('invoices', $modules);
        $this->assertContains('deliveries', $modules);
        $this->assertContains('purchase-orders', $modules);
        $this->assertContains('teams', $modules);
    }

    public function test_model_with_global_includes_settings(): void
    {
        $this->assertContains('settings', config('laravel-crm.model_with_global'));
    }

    public function test_user_interface_defaults_to_true(): void
    {
        $this->assertTrue((bool) config('laravel-crm.user_interface'));
    }

    public function test_encrypt_db_fields_defaults_to_false(): void
    {
        $this->assertFalse((bool) config('laravel-crm.encrypt_db_fields'));
    }

    public function test_teams_defaults_to_false(): void
    {
        $this->assertFalse((bool) config('laravel-crm.teams'));
    }
}
